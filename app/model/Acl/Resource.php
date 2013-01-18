<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 8:03
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\Acl;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity
 */
class Resource extends \SRS\Model\BaseEntity
{



    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $name;


    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="\SRS\model\Acl\Permission", mappedBy="resources", cascade={"persist"})
     */
    protected $permissions;


    public function __construct($name = null) {
        $this->name = $name;
        $permissions = new \Doctrine\Common\Collections\ArrayCollection();

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
     * @param \Doctrine\Common\Collections\ArrayCollection $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param mixed $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }


}
