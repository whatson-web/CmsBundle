<?php

namespace WH\CmsBundle\Controller\Frontend;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use WH\BackendBundle\Controller\Backend\BaseController;
use WH\LibBundle\Entity\Status;

/**
 * Class PageController
 *
 * @package WH\CmsBundle\Controller\Frontend
 */
class PageController extends BaseController
{

	/**
	 * @param         $id
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function viewAction($id, Request $request)
	{
		$em = $this->get('doctrine')->getManager();
		$page = $em->getRepository('WHCmsBundle:Page')->get(
			'one',
			array(
				'conditions' => array(
					'page.id'     => $id,
					'page.status' => Status::$STATUS_PUBLISHED,
				),
			)
		);
		if (!$page) {
			throw new NotFoundHttpException('Page introuvable');
		}

		$pageTemplate = $page->getPageTemplateSlug();
		if (!$pageTemplate) {
			throw new NotFoundHttpException('Aucun template de page n\'est associé à cette page');
		}
		$pageTemplates = $this->getParameter('wh_cms_templates');
		if (!isset($pageTemplates[$pageTemplate])) {
			if (!$pageTemplate) {
				throw new NotFoundHttpException('Le template de page associé à cette page n\'existe pas');
			}
		}
		$pageTemplate = $pageTemplates[$pageTemplate];

		$view = 'WHCmsBundle:Frontend/Page:view.html.twig';

		if (!empty($pageTemplate['frontController'])) {
			return $this->forward(
				$pageTemplate['frontController'],
				array(
					'id'      => $id,
					'request' => $request,
				)
			);
		}

		if (!empty($pageTemplate['frontView'])) {
			$view = $pageTemplate['frontView'];
		}

		return $this->render(
			$view,
			array(
				'page' => $page,
			)
		);
	}

}
