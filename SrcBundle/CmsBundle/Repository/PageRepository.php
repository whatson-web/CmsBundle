<?php

namespace CmsBundle\Repository;

use WH\LibBundle\Repository\BaseTreeRepository;

/**
 * Class PageRepository
 *
 * @package CmsBundle\Repository
 */
class PageRepository extends BaseTreeRepository
{

    public $joins = array(
        'parent' => array(),
        'url'    => array(),
        'metas'  => array(),
    );

    /**
     * @return string
     */
    public function getEntityNameQueryBuilder()
    {
        return 'page';
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBaseQuery()
    {
        $this->qb = $this
            ->createQueryBuilder($this->getEntityNameQueryBuilder())
            ->orderBy('page.lft', 'ASC');

        $this->addJoins(
            array(
                'parent',
                'url',
                'metas',
            )
        );

        return $this->qb;
    }
}
