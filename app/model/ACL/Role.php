<?php

namespace App\Model\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Role
{
    const GUEST = 'guest';
    const UNREGISTERED = 'unregistered';
    const UNAPPROVED = 'unapproved';
    const ATTENDEE = 'attentee';
    const SERVICE_TEAM = 'sevice_team';
    const LECTOR = 'lector';
    const ORGANIZER = 'organizer';
    const ADMIN = 'admin';

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string" unique=true) */
    protected $name;

    /** @ORM\ManyToMany(targetEntity="\SRS\model\User", mappedBy="roles", cascade={"persist"}) */
    protected $users;

    /** @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Permission", inversedBy="roles", cascade={"persist"}) */
    protected $permissions;

    /** @ORM\ManyToMany(targetEntity="\SRS\model\CMS\Page", mappedBy="roles", cascade={"persist"}) */
    protected $pages;

    /**
     * Pokud je role systemova, nelze ji smazat
     * @ORM\Column(type="boolean")
     */
    protected $system = true;

    /**
     * Lze o tuto roli zazadat pri registraci na seminar?
     * @ORM\Column(type="boolean")
     */
    protected $registerable = true;

    /**
     * Je role po registraci rovnou schvalena?
     * @ORM\Column(type="boolean")
     */
    protected $approvedAfterRegistration = false;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $registerableFrom;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $registerableTo;

    /**
     * Maximální počet osob v roli
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $capacity;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $fee;

    /** @ORM\Column(type="boolean", nullable=true) */
    protected $displayInList;

    /** @ORM\Column(type="boolean", nullable=true) */
    protected $displayCapacity;

    /** @ORM\Column(type="boolean", nullable=true) */
    protected $displayArrivalDeparture;

    /** @ORM\Column(type="boolean") */
    protected $syncedWithSkautIS = true;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role")
     * @ORM\JoinTable(name="role_role_incompatible",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_role_id", referencedColumnName="id")}
     *      )
     */
    protected $incompatibleRoles;

    /** @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", mappedBy="requiredRoles") */
    protected $requiredByRole;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", inversedBy="requiredByRole")
     * @ORM\JoinTable(name="role_role_required",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_role_id", referencedColumnName="id")}
     *      )
     */
    protected $requiredRoles;

    /** @ORM\ManyToMany(targetEntity="\SRS\model\Program\Category", mappedBy="registerableRoles", cascade={"persist"}) */
    protected $registerableCategories;
}