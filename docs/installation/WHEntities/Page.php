<?php

namespace WH\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use WH\CmsBundle\Model\Page as BasePage;

/**
 * Class Page
 *
 * @ORM\Table(name="page")
 * @ORM\Entity(repositoryClass="WH\CmsBundle\Repository\PageRepository")
 *
 * @Gedmo\Tree(type="nested")
 *
 * @package WH\CmsBundle\Entity
 */
class Page extends BasePage
{

}
