<?php

namespace WH\CmsBundle\Repository;

use WH\LibBundle\Repository\BaseTreeRepository;

/**
 * Class PageRepository
 *
 * @package WH\CmsBundle\Repository
 */
class PageRepository extends BaseTreeRepository
{

	/**
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getBaseQuery()
	{
		return $this
			->createQueryBuilder('page')
			->addSelect('parent')
			->addSelect('url')
			->addSelect('metas')
			->leftJoin('page.parent', 'parent')
			->leftJoin('page.url', 'url')
			->leftJoin('page.metas', 'metas')
			->orderBy('page.lft', 'ASC');
	}
}
