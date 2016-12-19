<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ResourceRepository")
 * @ORM\Table(name="resource")
 */
class Resource
{
    const ADMIN = 'admin';
    const CMS = 'cms';
    const ACL = 'acl';
    const PROGRAM = 'program';
    const CONFIGURATION = 'configuration';
    const EVIDENCE = 'evidence';
    const MAILING = 'mailing';

    public static $resources = [
        self::ADMIN,
        self::CMS,
        self::ACL,
        self::PROGRAM,
        self::CONFIGURATION,
        self::EVIDENCE,
        self::MAILING
    ];

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="\App\Model\ACL\Permission", mappedBy="resource", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $permissions;

    /**
     * Resource constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return ArrayCollection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param ArrayCollection $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }
}