<?php

declare(strict_types=1);

namespace App\Model\User;

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
#[ORM\Table(name: 'user')]
class User
{
    /**
     * Adresář pro ukládání profilových fotek.
     */
    public const PHOTO_PATH = 'user_photos';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Uživatelské jméno skautIS.
     */
    #[ORM\Column(type: 'string', unique: true, nullable: true, options: ['collation' => 'utf8mb4_bin'])]
    protected string|null $username = null;

    /**
     * E-mail.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $email = null;

    /**
     * Schválený.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $approved = true;

    /**
     * Jméno.
     */
    #[ORM\Column(type: 'string')]
    protected string|null $firstName = null;

    /**
     * Příjmení.
     */
    #[ORM\Column(type: 'string')]
    protected string|null $lastName = null;

    /**
     * Přezdívka.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $nickName = null;

    /**
     * Titul před jménem.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $degreePre = null;

    /**
     * Titul za jménem.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $degreePost = null;

    /**
     * Zobrazované jméno - Příjmení Jméno (Přezdívka).
     */
    #[ORM\Column(type: 'string')]
    protected string $displayName;

    /**
     * Zobrazované jméno lektora, včetně titulů.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $lectorName = null;

    /**
     * Bezpečnostní kód.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $securityCode = null;

    /**
     * Propojený účet.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $member = false;

    /**
     * Externí lektor.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $externalLector = false;

    /**
     * Jednotka.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $unit = null;

    /**
     * Pohlaví.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $sex = null;

    /**
     * Datum narození.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected DateTimeImmutable|null $birthdate = null;

    /**
     * Id uživatele ve skautIS.
     */
    #[ORM\Column(name: 'skautis_user_id', type: 'integer', unique: true, nullable: true)]
    protected int|null $skautISUserId = null;

    /**
     * Id osoby ve skautIS.
     */
    #[ORM\Column(name: 'skautis_person_id', type: 'integer', unique: true, nullable: true)]
    protected int|null $skautISPersonId = null;

    /**
     * Datum posledního přihlášení.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable|null $lastLogin = null;

    /**
     * O mně.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected string|null $about = null;

    /**
     * Ulice.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $street = null;

    /**
     * Město.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $city = null;

    /**
     * Poštovní směrovací číslo.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $postcode = null;

    /**
     * Stát.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $state = null;

    /**
     * Telefonní číslo.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $phone = null;

    /**
     * Zúčastnil se.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $attended = false;

    /**
     * Role.
     *
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users', cascade: ['persist'])]
    protected Collection $roles;

    /**
     * Přihlášky.
     *
     * @var Collection<int, Application>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Application::class, cascade: ['persist'])]
    protected Collection $applications;

    /**
     * Přihlášené programy.
     *
     * @var Collection<int, ProgramApplication>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProgramApplication::class, cascade: ['persist'])]
    protected Collection $programApplications;

    /**
     * Lektorované bloky.
     *
     * @var Collection<int, Block>
     */
    #[ORM\ManyToMany(targetEntity: Block::class, mappedBy: 'lectors', cascade: ['persist'])]
    protected Collection $lecturersBlocks;

    /**
     * Poplatek uživatele.
     */
    #[ORM\Column(type: 'integer')]
    protected int $fee = 0;

    /**
     * Zbývající poplatek uživatele.
     */
    #[ORM\Column(type: 'integer')]
    protected int $feeRemaining = 0;

    /**
     * Platební metoda.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $paymentMethod = null;

    /**
     * Datum poslední platby.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected DateTimeImmutable|null $lastPaymentDate = null;

    /**
     * Datum a čas vytvoření přihlášky rolí.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable|null $rolesApplicationDate = null;

    /**
     * Programové bloky, které jsou pro uživatele povinné, ale nemá je zapsané.
     *
     * @var Collection<int, Block>
     */
    #[ORM\ManyToMany(targetEntity: Block::class)]
    protected Collection $notRegisteredMandatoryBlocks;

    /**
     * Počet programových bloků, které jsou pro uživatele povinné, ale nemá je zapsané.
     */
    #[ORM\Column(type: 'integer')]
    protected int $notRegisteredMandatoryBlocksCount = 0;

    /**
     * Hodnoty vlastních polí přihlášky.
     *
     * @var Collection<int, CustomInputValue>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CustomInputValue::class, cascade: ['persist'])]
    protected Collection $customInputValues;

    /**
     * Neveřejná poznámka.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected string|null $note = null;

    /**
     * Fotka.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $photo = null;

    /**
     * Datum aktualizace fotky.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable|null $photoUpdate = null;

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

    public function getUsername(): string|null
    {
        return $this->username;
    }

    public function setUsername(string|null $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function setEmail(string|null $email): void
    {
        $this->email = $email;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): void
    {
        $this->approved = $approved;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
        $this->updateDisplayName();
        $this->updateLectorName();
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
        $this->updateDisplayName();
        $this->updateLectorName();
    }

    public function getNickName(): string|null
    {
        return $this->nickName;
    }

    public function setNickName(string|null $nickName): void
    {
        $this->nickName = $nickName;
        $this->updateDisplayName();
        $this->updateLectorName();
    }

    public function getDegreePre(): string|null
    {
        return $this->degreePre;
    }

    public function setDegreePre(string|null $degreePre): void
    {
        $this->degreePre = $degreePre;
        $this->updateLectorName();
    }

    public function getDegreePost(): string|null
    {
        return $this->degreePost;
    }

    public function setDegreePost(string|null $degreePost): void
    {
        $this->degreePost = $degreePost;
        $this->updateLectorName();
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Aktualizuje zobrazované jméno.
     */
    private function updateDisplayName(): void
    {
        $this->displayName = $this->lastName . ' ' . $this->firstName;

        if (! empty($this->nickName)) {
            $this->displayName .= ' (' . $this->nickName . ')';
        }
    }

    public function getLectorName(): string|null
    {
        return $this->lectorName;
    }

    /**
     * Aktualizuje jméno lektora.
     */
    private function updateLectorName(): void
    {
        $this->lectorName = '';

        if (! empty($this->degreePre)) {
            $this->lectorName .= $this->degreePre . ' ';
        }

        $this->lectorName .= $this->firstName . ' ' . $this->lastName;

        if (! empty($this->degreePost)) {
            $this->lectorName .= ', ' . $this->degreePost;
        }

        if (! empty($this->nickName)) {
            $this->lectorName .= ' (' . $this->nickName . ')';
        }
    }

    public function getSecurityCode(): string|null
    {
        return $this->securityCode;
    }

    public function setSecurityCode(string|null $securityCode): void
    {
        $this->securityCode = $securityCode;
    }

    /**
     * Má propojený účet?
     */
    public function isMember(): bool
    {
        return $this->member;
    }

    public function setMember(bool $member): void
    {
        $this->member = $member;
    }

    public function isExternalLector(): bool
    {
        return $this->externalLector;
    }

    public function setExternalLector(bool $externalLector): void
    {
        $this->externalLector = $externalLector;
    }

    public function getUnit(): string|null
    {
        return $this->unit;
    }

    public function setUnit(string|null $unit): void
    {
        $this->unit = $unit;
    }

    public function getSex(): string|null
    {
        return $this->sex;
    }

    public function setSex(string|null $sex): void
    {
        $this->sex = $sex;
    }

    public function getBirthdate(): DateTimeImmutable|null
    {
        return $this->birthdate;
    }

    public function setBirthdate(DateTimeImmutable|null $birthdate): void
    {
        $this->birthdate = $birthdate;
    }

    public function getAge(): int|null
    {
        return $this->birthdate !== null ? (new DateTimeImmutable())->diff($this->birthdate)->y : null;
    }

    public function getSkautISUserId(): int|null
    {
        return $this->skautISUserId;
    }

    public function setSkautISUserId(int|null $skautISUserId): void
    {
        $this->skautISUserId = $skautISUserId;
    }

    public function getSkautISPersonId(): int|null
    {
        return $this->skautISPersonId;
    }

    public function setSkautISPersonId(int|null $skautISPersonId): void
    {
        $this->skautISPersonId = $skautISPersonId;
    }

    public function getLastLogin(): DateTimeImmutable|null
    {
        return $this->lastLogin;
    }

    public function setLastLogin(DateTimeImmutable|null $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function getAbout(): string|null
    {
        return $this->about;
    }

    public function setAbout(string|null $about): void
    {
        $this->about = $about;
    }

    public function getStreet(): string|null
    {
        return $this->street;
    }

    public function setStreet(string|null $street): void
    {
        $this->street = $street;
    }

    public function getCity(): string|null
    {
        return $this->city;
    }

    public function setCity(string|null $city): void
    {
        $this->city = $city;
    }

    public function getPostcode(): string|null
    {
        return $this->postcode;
    }

    public function setPostcode(string|null $postcode): void
    {
        $this->postcode = $postcode;
    }

    public function getState(): string|null
    {
        return $this->state;
    }

    public function setState(string|null $state): void
    {
        $this->state = $state;
    }

    public function getPhone(): string|null
    {
        return $this->phone;
    }

    public function setPhone(string|null $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * Vrátí adresu uživatele.
     */
    public function getAddress(): string|null
    {
        if (empty($this->street) || empty($this->city) || empty($this->postcode)) {
            return null;
        }

        return $this->street . ', ' . $this->city . ', ' . $this->postcode;
    }

    public function isAttended(): bool
    {
        return $this->attended;
    }

    public function setAttended(bool $attended): void
    {
        $this->attended = $attended;
    }

    /** @return Collection<int, Role> */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /** @param Collection<int, Role> $roles */
    public function setRoles(Collection $roles): void
    {
        foreach ($this->roles as $role) {
            $this->removeRole($role);
        }

        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    public function addRole(Role $role): void
    {
        if (! $this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
        }
    }

    public function removeRole(Role $role): void
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            $role->removeUser($this);
        }
    }

    /**
     * Je uživatel v roli?
     */
    public function isInRole(Role $role): bool
    {
        return $this->roles->contains($role);
    }

    /**
     * Vrací, zda má uživatel nějakou roli, která nemá cenu podle podakcí.
     */
    public function hasFixedFeeRole(): bool
    {
        return $this->roles->exists(static fn (int $key, Role $role) => $role->getFee() !== null);
    }

    /**
     * Vrátí role uživatele oddělené čárkou.
     */
    public function getRolesText(): string
    {
        $rolesNames = [];
        foreach ($this->roles as $role) {
            $rolesNames[] = $role->getName();
        }

        return implode(', ', $rolesNames);
    }

    /**
     * Má uživatel oprávnění k prostředku?
     */
    public function isAllowed(string $resource, string $permission): bool
    {
        foreach ($this->roles as $r) {
            foreach ($r->getPermissions() as $p) {
                if ($p->getResource()->getName() === $resource && $p->getName() === $permission) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Je uživatel oprávněn upravovat blok?
     */
    public function isAllowedModifyBlock(Block $block): bool
    {
        if ($this->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS)) {
            return true;
        }

        return $this->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_OWN_PROGRAMS) && $block->getLectors()->contains($this);
    }

    /** @return Collection<int, Application> */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Application $application): void
    {
        if (! $this->applications->contains($application)) {
            $this->applications->add($application);
        }
    }

    /**
     * Vrátí platné přihlášky.
     *
     * @return Collection<int, Application>
     */
    public function getValidApplications(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('validTo'))
            ->orderBy(['applicationId' => 'ASC']);

        return $this->applications->matching($criteria);
    }

    /**
     * Vrátí nezrušené přihlášky.
     *
     * @return Collection<int, Application>
     */
    public function getNotCanceledApplications(): Collection
    {
        return $this->getValidApplications()->filter(static fn (Application $application) => ! $application->isCanceled());
    }

    /**
     * Vrátí nezrušené přihlášky na rolí.
     *
     * @return Collection<int, RolesApplication>
     */
    public function getNotCanceledRolesApplications(): Collection
    {
        return $this->getNotCanceledApplications()->filter(static fn (Application $application) => $application instanceof RolesApplication);
    }

    /**
     * Vrátí nezrušené přihlášky na podakce.
     *
     * @return Collection<int, SubeventsApplication>
     */
    public function getNotCanceledSubeventsApplications(): Collection
    {
        return $this->getNotCanceledApplications()->filter(static fn (Application $application) => $application instanceof SubeventsApplication);
    }

    /**
     * Vrácí zaplacené přihlášky.
     *
     * @return Collection<int, Application>
     */
    public function getPaidApplications(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->andX(
                Criteria::expr()->isNull('validTo'),
                Criteria::expr()->eq('state', ApplicationState::PAID),
            ));

        return $this->applications->matching($criteria);
    }

    /**
     * Vrátí přihlášky, které jsou zaplacené nebo zdarma.
     *
     * @return Collection<int, Application>
     */
    public function getPaidAndFreeApplications(): Collection
    {
        return $this->applications->filter(static fn (Application $application) => $application->getValidTo() === null && (
                $application->getState() === ApplicationState::PAID_FREE ||
                $application->getState() === ApplicationState::PAID));
    }

    /**
     * Vrátí přihlášky čekající na platbu.
     *
     * @return Collection<int, Application>
     */
    public function getWaitingForPaymentApplications(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->andX(
                Criteria::expr()->isNull('validTo'),
                Criteria::expr()->eq('state', ApplicationState::WAITING_FOR_PAYMENT),
            ));

        return $this->applications->matching($criteria);
    }

    /**
     * Vrátí přihlášky rolí čekající na platbu.
     *
     * @return Collection<int, RolesApplication>
     */
    public function getWaitingForPaymentRolesApplications(): Collection
    {
        return $this->getWaitingForPaymentApplications()->filter(static fn (Application $application) => $application instanceof RolesApplication);
    }

    /**
     * Vrací přihlášku rolí.
     */
    public function getRolesApplication(): RolesApplication|null
    {
        foreach ($this->getNotCanceledRolesApplications() as $application) {
            return $application;
        }

        return null;
    }

    /**
     * Vrátí přihlášky podakcí čekající na platbu.
     *
     * @return Collection<int, SubeventsApplication>
     */
    public function getWaitingForPaymentSubeventsApplications(): Collection
    {
        return $this->getWaitingForPaymentApplications()->filter(static fn (Application $application) => $application instanceof SubeventsApplication);
    }

    /**
     * Vrací zda uživatel zaplatil nějakou přihlášku.
     */
    public function hasPaidAnyApplication(): bool
    {
        return ! $this->getPaidApplications()->isEmpty();
    }

    /**
     * Vrací zda uživatel zaplatil všechny přihlášky.
     */
    public function hasPaidEveryApplication(): bool
    {
        return $this->getValidApplications()->forAll(static fn (int $key, Application $application) => $application->getState() !== ApplicationState::WAITING_FOR_PAYMENT);
    }

    /**
     * Vrací zda uživatel zaplatil přihlášku rolí.
     */
    public function hasPaidRolesApplication(): bool
    {
        return $this->getRolesApplication()->getState() !== ApplicationState::WAITING_FOR_PAYMENT;
    }

    /** @return Collection<int, Block> */
    public function getLecturersBlocks(): Collection
    {
        return $this->lecturersBlocks;
    }

    public function addLecturersBlock(Block $block): void
    {
        if (! $this->lecturersBlocks->contains($block)) {
            $this->lecturersBlocks->add($block);
            $block->addLector($this);
        }
    }

    public function removeLecturersBlock(Block $block): void
    {
        if ($this->lecturersBlocks->contains($block)) {
            $this->lecturersBlocks->removeElement($block);
            $block->removeLector($this);
        }
    }

    public function getFee(): int
    {
        return $this->fee;
    }

    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    /**
     * Je uživatel platící?
     */
    public function isPaying(): bool
    {
        return $this->getFee() !== 0;
    }

    public function getFeeRemaining(): int
    {
        return $this->feeRemaining;
    }

    public function setFeeRemaining(int $feeRemaining): void
    {
        $this->feeRemaining = $feeRemaining;
    }

    public function getPaymentMethod(): string|null
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string|null $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getLastPaymentDate(): DateTimeImmutable|null
    {
        return $this->lastPaymentDate;
    }

    public function setLastPaymentDate(DateTimeImmutable|null $lastPaymentDate): void
    {
        $this->lastPaymentDate = $lastPaymentDate;
    }

    public function getRolesApplicationDate(): DateTimeImmutable|null
    {
        return $this->rolesApplicationDate;
    }

    public function setRolesApplicationDate(DateTimeImmutable|null $rolesApplicationDate): void
    {
        $this->rolesApplicationDate = $rolesApplicationDate;
    }

    /** @return Collection<int, Block> */
    public function getNotRegisteredMandatoryBlocks(): Collection
    {
        return $this->notRegisteredMandatoryBlocks;
    }

    public function getNotRegisteredMandatoryBlocksText(): string
    {
        return implode(', ', $this->notRegisteredMandatoryBlocks->map(static fn (Block $block) => $block->getName())->toArray());
    }

    /** @param Collection<int, Block> $notRegisteredMandatoryBlocks */
    public function setNotRegisteredMandatoryBlocks(Collection $notRegisteredMandatoryBlocks): void
    {
        $this->notRegisteredMandatoryBlocks->clear();
        foreach ($notRegisteredMandatoryBlocks as $notRegisteredMandatoryBlock) {
            $this->notRegisteredMandatoryBlocks->add($notRegisteredMandatoryBlock);
        }

        $this->notRegisteredMandatoryBlocksCount = $this->notRegisteredMandatoryBlocks->count();
    }

    public function getNotRegisteredMandatoryBlocksCount(): int
    {
        return $this->notRegisteredMandatoryBlocksCount;
    }

    /** @return Collection<int, CustomInputValue> */
    public function getCustomInputValues(): Collection
    {
        return $this->customInputValues;
    }

    public function addCustomInputValue(CustomInputValue $value): void
    {
        if (! $this->customInputValues->contains($value)) {
            $this->customInputValues->add($value);
        }
    }

    public function getCustomInputValue(CustomInput $customInput): CustomInputValue|null
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()
                ->eq('input', $customInput));

        $matchingCustomInputValues = $this->customInputValues->matching($criteria);

        if ($matchingCustomInputValues->count() === 0) {
            return null;
        }

        return $matchingCustomInputValues->first();
    }

    public function getNote(): string|null
    {
        return $this->note;
    }

    public function setNote(string|null $note): void
    {
        $this->note = $note;
    }

    public function getPhoto(): string|null
    {
        return $this->photo;
    }

    public function setPhoto(string|null $photo): void
    {
        $this->photo = $photo;
    }

    public function getPhotoUpdate(): DateTimeImmutable|null
    {
        return $this->photoUpdate;
    }

    public function setPhotoUpdate(DateTimeImmutable|null $photoUpdate): void
    {
        $this->photoUpdate = $photoUpdate;
    }

    /**
     * Vrací podakce uživatele.
     *
     * @return Collection<int, Subevent>
     */
    public function getSubevents(): Collection
    {
        $subevents = new ArrayCollection();

        foreach ($this->getNotCanceledSubeventsApplications() as $application) {
            foreach ($application->getSubevents() as $subevent) {
                $subevents->add($subevent);
            }
        }

        return $subevents;
    }

    /**
     * Vrátí podakce uživatele oddělené čárkou.
     */
    public function getSubeventsText(): string
    {
        $subeventsNames = $this->getSubevents()->map(static fn (Subevent $subevent) => $subevent->getName());

        return implode(', ', $subeventsNames->toArray());
    }

    /**
     * Vrací, zda je uživatel přihlášen na podakci.
     */
    public function hasSubevent(Subevent $subevent): bool
    {
        return $this->getSubevents()->contains($subevent);
    }

    /**
     * Vrácí, zda má uživatel zaplacenou přihlášku s podakcí.
     */
    public function hasPaidSubevent(Subevent $subevent): bool
    {
        foreach ($this->getPaidAndFreeApplications() as $application) {
            if ($application instanceof SubeventsApplication && $application->getSubevents()->contains($subevent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vrátí variabilní symboly oddělené čárkou.
     */
    public function getVariableSymbolsText(): string
    {
        $variableSymbols = $this->getNotCanceledApplications()->map(static fn (Application $application) => $application->getVariableSymbolText());

        return implode(', ', $variableSymbols->toArray());
    }

    public function isAttendee(Program $program): bool
    {
        return ! $this->programApplications->matching(
            Criteria::create()->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('program', $program),
                    Criteria::expr()->eq('alternate', false),
                ),
            ),
        )->isEmpty();
    }

    public function isAlternate(Program $program): bool
    {
        return ! $this->programApplications->matching(
            Criteria::create()->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('program', $program),
                    Criteria::expr()->eq('alternate', true),
                ),
            ),
        )->isEmpty();
    }
}
