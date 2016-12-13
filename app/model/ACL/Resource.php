<?php

namespace App\Model\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Resource
{
    const ADMIN = 'admin';
    const CMS = 'cms';
    const ACL = 'acl';
    const PROGRAM = 'program';
    const ROOM = 'room';
    const CATEGORY = 'category';
    const CONFIGURATION = 'configuration';
    const EVIDENCE = 'evidence';
    const MAILING = 'mailing';

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string" unique=true) */
    protected $name;

    /** @ORM\OneToMany(targetEntity="Permission", mappedBy="resources", cascade={"persist"}) */
    protected $permissions;
}