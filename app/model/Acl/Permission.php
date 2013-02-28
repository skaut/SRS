<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 8:17
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\Acl;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Permission extends \SRS\Model\BaseEntity
{
    const MANAGE = 'Spravovat';
    const ACCESS = 'Přístup';
    const MANAGE_OWN_PROGRAMS = 'Spravovat vlastní Programy';
    const MANAGE_ALL_PROGRAMS = 'Spravovat Všechny Programy';
    const MANAGE_HARMONOGRAM = 'Upravovat harmonogram';
    const CHOOSE_PROGRAMS = 'Vybírat si programy';



    /**
     * @ORM\Column
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", mappedBy="permissions", cascade={"persist"})
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $roles;


    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Acl\Resource", inversedBy="permissions", cascade={"persist"})
     * @var \SRS\Model\Acl\Resource
     */
    protected $resource;


    public function __construct($name, $resource) {
        $this->name = $name;
        $this->resource = $resource;
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \SRS\Model\Acl\Resource $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return \SRS\Model\Acl\Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

}
