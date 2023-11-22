<?php

declare(strict_types=1);

namespace App\Model\Group;

use App\Model\Acl\Permission;
use App\Model\Acl\Role;
use App\Model\Acl\SrsResource;
use App\Model\Application\Application;
use App\Model\Application\RolesApplication;
use App\Model\Application\SubeventsApplication;
use App\Model\CustomInput\CustomInput;
use App\Model\CustomInput\CustomInputValue;
use App\Model\Enums\ApplicationState;
use App\Model\Program\Block;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\Structure\Subevent;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

use function implode;

/**
 * Entita uživatele.
 */
#[ORM\Entity]
#[ORM\Table(name: 'group')]
class Group
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Nazev skupiny
     */
    #[ORM\Column(type: 'string')]
    protected string|null $name = null;

    /**
     * Vedouci skupiny
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    protected int|null $leaderId = null;

    /**
     * Vedouci skupiny - email
     */
    #[ORM\Column(type: 'string')]
    protected string|null $leaderEmail = null;

    /**
     * Datum vytvoreni
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable|null $createDate = null;

    /**
     * Status
     */
    #[ORM\Column(type: 'integer')]
    protected int|null $groupStatusId = null;

    /**
     * Pocet mist
     */
    #[ORM\Column(type: 'string')]
    protected string|null $places = null;

    /**
     * Cena
     */
    #[ORM\Column(type: 'integer')]
    protected int $price = 0;

    /**
     * Poznamka
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected string|null $note = null;

    public function __construct()
    {
        $this->applications                 = new ArrayCollection();
        $this->roles                        = new ArrayCollection();
        $this->programApplications          = new ArrayCollection();
        $this->lecturersBlocks              = new ArrayCollection();
        $this->notRegisteredMandatoryBlocks = new ArrayCollection();
        $this->customInputValues            = new ArrayCollection();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string|null $name): void
    {
        $this->name = $name;
    }

    public function getLeaderId(): int|null
    {
        return $this->leaderId;
    }

    public function setLeaderId(int|null $leaderId): void
    {
        $this->leaderId = $leaderId;
    }

    public function getLeaderEmail(): string|null
    {
        return $this->leaderEmail;
    }

    public function setLeaderEmail(string|null $leaderEmail): void
    {
        $this->leaderEmail = $leaderEmail;
    }

    public function getCreateDate(): DateTimeImmutable|null
    {
        return $this->createDate;
    }

    public function setCreateDate(DateTimeImmutable|null $createDate): void
    {
        $this->createDate = $createDate;
    }

    public function getGroupStatusId(): int|null
    {
        return $this->groupStatusId;
    }

    public function setGroupStatusId(int|null $groupStatusId): void
    {
        $this->groupStatusId = $groupStatusId;
    }
    
    public function getPlaces(): string|null
    {
        return $this->places;
    }

    public function setPlaces(string|null $places): void
    {
        $this->places = $places;
    }

    public function getPrice(): int|null
    {
        return $this->price;
    }

    public function setPrice(int|null $price): void
    {
        $this->price = $price;
    }
    
    public function getNote(): string|null
    {
        return $this->note;
    }

    public function setNote(string|null $note): void
    {
        $this->note = $note;
    }

    

}
