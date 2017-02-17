<?php

namespace CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use WH\CmsBundle\Model\Page as BasePage;

/**
 * Class Page
 *
 * @ORM\Table(name="page")
 * @ORM\Entity(repositoryClass="CmsBundle\Repository\PageRepository")
 *
 * @Gedmo\Tree(type="nested")
 *
 * @package CmsBundle\Entity
 */
class Page extends BasePage
{

}
