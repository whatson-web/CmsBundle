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
        $em = $this->container->get('doctrine')->getManager();

        $entityPathConfig = $this->getEntityPathConfig();

        $data = $em->getRepository($this->getRepositoryName($entityPathConfig))->get(
            'one',
            array(
                'conditions' => array(
                    Inflector::camelize($entityPathConfig['entity']) . '.id' => $id,
                ),
            )
        );

        if (!$data) {
            return $this->redirect($this->getActionUrl($entityPathConfig, 'index'));
        }

        $pageTemplate = '';
        if ($data->getPageTemplateSlug()) {
            $pageTemplate = $this->getParameter('wh_cms_templates')[$data->getPageTemplateSlug()];
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

        $config = $this->getConfig($entityPathConfig, 'update');
        $globalConfig = $this->getGlobalConfig($entityPathConfig);

        $renderVars['globalConfig'] = $globalConfig;

        $renderVars['title'] = $config['title'];

        $formFields = $this->getFormFields($config['formFields'], $entityPathConfig);

        $form = $this->getEntityForm($formFields, $entityPathConfig, $data);

        $renderVars['breadcrumb'] = $this->getBreadcrumb(
            $config['breadcrumb'],
            $entityPathConfig,
            $data
        );

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();

            $em->persist($data);
            $em->flush();

            $redirectUrl = $this->getActionUrl($entityPathConfig, 'index', $data);
            if ($form->has('saveAndStay') && $form->get('saveAndStay')->isClicked()) {
                $redirectUrl = $this->getActionUrl($entityPathConfig, 'update', $data);
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
            $form->setData($data);
        }

        $form = $form->createView();
        $renderVars['form'] = $form;
        $renderVars['formFields'] = $formFields;

        if (!empty($config['central']['viewLink']['action'])) {
            $config['central']['viewLink']['url'] = $this->getActionUrl(
                $entityPathConfig,
                $config['central']['viewLink']['action'],
                $data
            );
        }

        foreach ($config['central']['tabs'] as $tabSlug => $tabProperties) {
            if (isset($tabProperties['iframeContent'])) {
                $config['central']['tabs'][$tabSlug]['iframeContent']['url'] = $this->getActionUrl(
                    $entityPathConfig,
                    $tabProperties['iframeContent']['action'],
                    $data
                );
            }
        }

        $renderVars['central'] = $config['central'];

        foreach ($config['column']['panelZones'] as $key => $panelZone) {
            $panelZone['form'] = $form;
            $panelZone['formFields'] = $this->getFormFields($panelZone['fields'], $entityPathConfig);

            unset($panelZone['fields']);

            if (isset($panelZone['footerListFormButtons'])) {
                foreach ($panelZone['footerListFormButtons'] as $field => $footerListFormButton) {
                    $footerListFormButton = array_merge($footerListFormButton, $config['formFields'][$field]);
                    $footerListFormButton['form'] = $form;

                    $panelZone['footerListFormButtons'][$field] = $footerListFormButton;
                }
            }
            $config['column']['panelZones'][$key] = $panelZone;
        }

        $renderVars['column'] = $config['column'];

        $view = '@WHBackendTemplate/BackendTemplate/View/update.html.twig';

        $renderVars = $this->get('bk.wh.back.update_controller')->translateRenderVars($entityPathConfig, $renderVars);

        return $this->container->get('templating')->renderResponse(
            $view,
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
