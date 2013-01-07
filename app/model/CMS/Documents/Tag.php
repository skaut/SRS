<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 7.1.13
 * Time: 16:55
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\CMS\Documents;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @property string $name
 * @property \Doctrine\Common\Collections\ArrayCollection $documents
 */
class Tag extends \SRS\Model\BaseEntity
{


    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\CMS\Documents\Document", mappedBy="tags", cascade={"persist"})
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $documents;

    /**
     * @ORM\Column
     * @var string
     */
    protected $name;

}
