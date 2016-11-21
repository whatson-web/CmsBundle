<?php

namespace WH\CmsBundle\Controller\Backend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use WH\BackendBundle\Controller\Backend\BaseController;

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
		$updateController = $this->get('bk.wh.back.update_controller');

		return $updateController->update($this->getEntityPathConfig(), $id, $request);
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

}
