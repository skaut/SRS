<?php

namespace App\Model\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Permission
{
    const MANAGE = 'manage';
    const ACCESS = 'access';
    const MANAGE_OWN_PROGRAMS = 'manage_own_programs';
    const MANAGE_ALL_PROGRAMS = 'manage_all_programs';
    const MANAGE_HARMONOGRAM = 'manage_harmonogram';
    const CHOOSE_PROGRAMS = 'choose_programs';

//    public static $permissions = [
//        self::MANAGE,
//        self::ACCESS,
//        self::MANAGE_OWN_PROGRAMS,
//        self::MANAGE_ALL_PROGRAMS,
//        self::MANAGE_HARMONOGRAM,
//        self::CHOOSE_PROGRAMS
//    ];

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string") */
    protected $name;

    /** @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", mappedBy="permissions", cascade={"persist"}) */
    protected $roles;

    /** @ORM\ManyToOne(targetEntity="\SRS\Model\Acl\Resource", inversedBy="permissions", cascade={"persist"}) */
    protected $resource;
}