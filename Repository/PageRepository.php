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
			->leftJoin('page.parent', 'parent')
			->orderBy('page.lft', 'ASC');
	}
}
