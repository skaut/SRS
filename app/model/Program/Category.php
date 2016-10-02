<?php

namespace SRS\Model\Program;
use Doctrine\ORM\Mapping as ORM,
    JMS\Serializer\Annotation as JMS;

/**
 * Entita kategirii
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Program\CategoryRepository")
 * @JMS\ExclusionPolicy("none")
 * @property \Doctrine\Common\Collections\ArrayCollection $blocks
 * @property string $name
 * @property \Doctrine\Common\Collections\ArrayCollection $registerableRoles
 */
class Category extends \SRS\Model\BaseEntity
{
    /**
     * @ORM\OneToMany(targetEntity="\SRS\Model\Program\Block", mappedBy="room", cascade={"persist"}, orphanRemoval=true)
     * @JMS\Type("ArrayCollection<SRS\Model\Program\Block>")
     * @JMS\Exclude
     */
    protected $blocks;

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", inversedBy="registerableCategories", cascade={"persist"})
     * @var mixed
     */
    protected $registerableRoles;


    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setBlocks($blocks)
    {
        $this->blocks = $blocks;
    }

    public function getBlocks()
    {
        return $this->blocks;
    }
}

/**
 * Vlastni repozitar pro praci s místnostmi
 */
class CategoryRepository extends \Nella\Doctrine\Repository
{
    public $entity = '\SRS\Model\Program\Category';

    public function findAll()
    {
        $query = $this->_em->createQuery("SELECT c FROM {$this->entity} c");
        return $query->getResult();
    }

}
