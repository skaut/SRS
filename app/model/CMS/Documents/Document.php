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
 * @property string $file
 * @property \Doctrine\Common\Collections\ArrayCollection $tags
 *
 */
class Document extends \SRS\Model\BaseEntity
{

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\CMS\Documents\Tag", inversedBy="documents", cascade={"persist"})
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $tags;

    /**
     * @ORM\Column
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column
     * @var string
     */
    protected $file;


    public function setFile($file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }


}
