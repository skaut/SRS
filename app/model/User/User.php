<?php

namespace App\Model\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string", unique=true) */
    protected $username;

    /** @ORM\Column(type="string") */
    protected $email;

    /** @ORM\ManyToMany(targetEntity="Role", inversedBy="users", cascade={"persist"}) */
    protected $roles;

    /**
     * @ORM\ManyToMany(targetEntity="Program", mappedBy="attendees", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     */
    protected $programs;

    /** @ORM\Column(type="boolean") */
    protected $approved = True;

    /** @ORM\Column(type="string") */
    protected $firstName;

    /** @ORM\Column(type="string") */
    protected $lastName;

    /** @ORM\Column(type="string", nullable=true) */
    protected $nickName;

    /** @ORM\Column(type="string") */
    protected $displayName;

    /** @ORM\Column(type="string", nullable=true) */
    protected $securityCode;

    /** @ORM\Column(type="boolean") */
    protected $member = false;

    /** @ORM\Column(type="string", nullable=true) */
    protected $unit;

    /** @ORM\Column(type="string", nullable=true) */
    protected $sex;

    /** @ORM\Column(type="date") */
    protected $birthdate;

    /** @ORM\Column(type="integer", unique=true) */
    protected $skautISUserId;

    /** @ORM\Column(type="integer", unique=true) */
    protected $skautISPersonId;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $firstLogin;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $lastLogin;

    /** @ORM\Column(type="text", nullable=true) */
    protected $about;

    /** @ORM\Column(type="string", nullable=true) */
    protected $street;

    /** @ORM\Column(type="string", nullable=true) */
    protected $city;

    /** @ORM\Column(type="string", nullable=true) */
    protected $postcode;

    /** @ORM\Column(type="string", nullable=true) */
    protected $state;

    /** @ORM\Column(type="string", nullable=true) */
    protected $paymentMethod;

    /** @ORM\Column(type="date", nullable=true) */
    protected $paymentDate;

    /** @ORM\Column(type="string", nullable=true) */
    protected $variableSymbol;

    /** @ORM\Column(type="boolean") */
    protected $attended = false;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $arrival;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $departure;

    /** @ORM\Column(type="string", nullable=true) */
    protected $membershipType;

    /** @ORM\Column(type="string", nullable=true) */
    protected $membershipCategory;

    /** @ORM\Column(type="date", nullable=true) */
    protected $incomeProofPrintedDate;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean0 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean1 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean2 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean3 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean4 = false;

    /** @ORM\Column(type="text", nullable=true) */
    protected $customText0;

    /** @ORM\Column(type="text", nullable=true) */
    protected $customText1;

    /** @ORM\Column(type="text", nullable=true) */
    protected $note;
}