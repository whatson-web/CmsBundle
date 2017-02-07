<?php

namespace WH\CmsBundle\Controller\Backend;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Yaml;
use WH\BackendBundle\Controller\Backend\BaseController;
use WH\LibBundle\Utils\Inflector;

/**
 * @Route("/admin/pages")
 *
 * Class PageController
 *
 * @package WH\CmsBundle\Controller\Backend
 */
class PageController extends BaseController
{

	public $bundlePrefix = 'WH';
	public $bundle = 'CmsBundle';
	public $entity = 'Page';

	/**
	 * @Route("/index/{parentId}", name="bk_wh_cms_page_index", requirements={"parentId": ".*"}, defaults={"parentId": null})
	 *
	 * @param         $parentId
	 * @param Request $request
	 *
	 * @return string
	 */
	public function indexAction($parentId = null, Request $request)
	{
		$arguments = array(
			'parent.id' => $parentId,
		);

		$indexController = $this->get('bk.wh.back.index_controller');

		return $indexController->index($this->getEntityPathConfig(), $request, $arguments);
	}

	/**
	 * @Route("/create/", name="bk_wh_cms_page_create")
	 *
	 * @param Request $request
	 *
	 * @return mixed
	 */
	public function createAction(Request $request)
	{
		$createController = $this->get('bk.wh.back.create_controller');

		return $createController->create($this->getEntityPathConfig(), $request);
	}

	/**
	 * @Route("/update/{id}", name="bk_wh_cms_page_update")
	 *
	 * @param         $id
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function updateAction($id, Request $request)
	{
		$this->backendTranslator = $this->container->get('bk.wh.back.translator');

		$entityPathConfig = $this->getEntityPathConfig();
		$this->setTranslateDomain($entityPathConfig);

		$em = $this->container->get('doctrine')->getManager();

		$page = $em->getRepository($this->getRepositoryName($entityPathConfig))->get(
			'one',
			array(
				'conditions' => array(
					Inflector::camelize($entityPathConfig['entity']) . '.id' => $id,
				),
			)
		);
		if (!$page) {
			return $this->redirect($this->getActionUrl($entityPathConfig, 'index'));
		}
		$pageTemplate = '';
		if ($page->getPageTemplateSlug()) {
			$pageTemplate = $this->getParameter('wh_cms_templates')[$page->getPageTemplateSlug()];
		}
		if (!empty($pageTemplate['backendController'])) {

			return $this->forward(
				$pageTemplate['backendController'] . ':update',
				array(
					'id'      => $id,
					'request' => $request,
				)
			);
		}

		$renderVars = array();

		if (!empty($pageTemplate['updateConfig'])) {
			$config = $this->getOverrideConfig($pageTemplate['updateConfig']);
		} else {
			$config = $this->getConfig($entityPathConfig, 'update');
		}
		$globalConfig = $this->getGlobalConfig($entityPathConfig);

		$renderVars['globalConfig'] = $globalConfig;

        $renderVars['title'] = $this->backendTranslator->trans($config['title']);

		$formFields = $this->getFormFields($config['formFields'], $entityPathConfig);

		$form = $this->getEntityForm($formFields, $entityPathConfig, $page);

		$renderVars['breadcrumb'] = $this->getBreadcrumb(
			$config['breadcrumb'],
			$entityPathConfig,
			$page
		);

		$form->handleRequest($request);

		if ($form->isSubmitted()) {

			$page = $form->getData();

			$em->persist($page);
			$em->flush();

			$redirectUrl = $this->getActionUrl($entityPathConfig, 'index', $page);
			if ($form->has('saveAndStay') && $form->get('saveAndStay')->isClicked()) {
				$redirectUrl = $this->getActionUrl($entityPathConfig, 'update', $page);
			}

			if ($request->isXmlHttpRequest()) {

				return new JsonResponse(
					array(
						'success'  => true,
						'redirect' => $redirectUrl,
					)
				);
			}

			return $this->redirect($redirectUrl);
		} else {

			$form->setData($page);
		}

		$form = $form->createView();
		$renderVars['form'] = $form;
		$renderVars['formFields'] = $formFields;

        if (!empty($config['central']['viewLink']['name'])) {
            $config['central']['viewLink']['name'] = $this->backendTranslator->trans(
                $config['central']['viewLink']['name']
            );
        }
		if (!empty($config['central']['viewLink']['action'])) {
			$config['central']['viewLink']['url'] = $this->getActionUrl(
				$entityPathConfig,
				$config['central']['viewLink']['action'],
				$page
			);
		}

        foreach ($config['central']['tabs'] as $tabSlug => $tabProperties) {
            $config['central']['tabs'][$tabSlug]['name'] = $this->backendTranslator->trans(
                $config['central']['tabs'][$tabSlug]['name']
            );
            if (!empty($tabProperties['formZones'])) {
                foreach ($tabProperties['formZones'] as $formZoneSlug => $formZone) {
                    if (isset($formZone['title'])) {
                        $config['central']['tabs'][$tabSlug]['formZones'][$formZoneSlug]['title'] = $this->backendTranslator->trans(
                            $config['central']['tabs'][$tabSlug]['formZones'][$formZoneSlug]['title']
                        );
                    }
                }
            }
        }

		$renderVars['central'] = $config['central'];

		foreach ($config['column']['panelZones'] as $key => $panelZone) {
            $panelZone['headerLabel'] = $this->backendTranslator->trans($panelZone['headerLabel']);

			$panelZone['form'] = $form;
			$panelZone['formFields'] = $this->getFormFields($panelZone['fields'], $entityPathConfig);

			unset($panelZone['fields']);

			if (isset($panelZone['footerListFormButtons'])) {

				foreach ($panelZone['footerListFormButtons'] as $field => $footerListFormButton) {

					$footerListFormButton = array_merge($footerListFormButton, $config['formFields'][$field]);
					$footerListFormButton['form'] = $form;
                    $footerListFormButton['label'] = $this->backendTranslator->trans($footerListFormButton['label']);

					$panelZone['footerListFormButtons'][$field] = $footerListFormButton;
				}
			}
			$config['column']['panelZones'][$key] = $panelZone;
		}

		$renderVars['column'] = $config['column'];

		return $this->container->get('templating')->renderResponse(
			'@WHBackendTemplate/BackendTemplate/View/update.html.twig',
			$renderVars
		);
	}

	/**
	 * @Route("/delete/{id}", name="bk_wh_cms_page_delete")
	 *
	 * @param         $id
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction($id)
	{
		$deleteController = $this->get('bk.wh.back.delete_controller');

		return $deleteController->delete($this->getEntityPathConfig(), $id);
	}

	/**
	 * @Route("/order/", name="bk_wh_cms_page_order")
	 *
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function orderAction(Request $request)
	{
		$orderController = $this->get('bk.wh.back.order_controller');

		return $orderController->order($this->getEntityPathConfig(), $request);
	}

	/**
	 * @param $action
	 *
	 * @return mixed
	 */
	private function getOverrideConfig($action)
	{
		$ymlPath = $this->get('kernel')->getRootDir();
		$ymlPath .= '/Resources/WHCmsBundle/config/Backend/Page/' . $action . '.yml';

		if (!file_exists($ymlPath)) {
			throw new NotFoundHttpException(
				'Le fichier de configuration n\'existe pas. Il devrait être ici : ' . $ymlPath
			);
		}

		$config = Yaml::parse(file_get_contents($ymlPath));
		if ($this->validConfig($config)) {
			return $config;
		}

		return array();
	}

}
