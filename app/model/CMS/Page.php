<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:27
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="\SRS\Model\CMS\PageRepository")
 *
 *
 * @property-read int $id
 * @property string $name
 * @property string $slug
 * @property bool $public
 * @property \Doctrine\Common\Collections\ArrayCollection $roles
 * @property \Doctrine\Common\Collections\ArrayCollection $contents
 * @property int $position

 */
class Page extends \SRS\Model\BaseEntity
{

    /**
     * @ORM\Column
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $slug;


    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $public = false;



    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", inversedBy="pages")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $roles;




    /**
     * @ORM\OneToMany(targetEntity="\SRS\model\CMS\Content", mappedBy="page", cascade={"persist"})
     * @ORM\OrderBy({"position" = "ASC"})
     * @var  \Doctrine\Common\Collections\ArrayCollection
     */
    protected $contents;


    public function __construct($name, $slug) {
        $this->name = $name;
        $this->slug = $slug;
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contents = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setContents($contents)
    {
        $this->contents = $contents;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setPublic($public)
    {
        $this->public = $public;
    }

    public function getPublic()
    {
        return $this->public;
    }
}


class PageRepository extends \Doctrine\ORM\EntityRepository
{
    public $entity = '\SRS\Model\CMS\Page';

    public function getCount()
    {
        return $this->_em->createQuery('SELECT count (p.id) FROM '.$this->entity. ' p' )
            ->getSingleScalarResult();
    }

    public function slugToId($slug) {
        try {
            return $this->_em->createQuery("SELECT p.id FROM ".$this->entity. " p WHERE p.slug = '{$slug}' ")
                ->getSingleResult();
        }
        catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function IdToSlug($id) {
        try {
        return $this->_em->createQuery("SELECT p.slug FROM ".$this->entity. " p WHERE p.id = '{$id}' ")
            ->getSingleScalarResult();
        }
        
        catch(\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }


}
