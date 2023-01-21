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
use App\Model\Enums\TroopApplicationState;
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
    private ?int $id = null;

    /**
     * Uživatelské jméno skautIS.
     */
    #[ORM\Column(type: 'string', unique: true, nullable: true, options: ['collation' => 'utf8mb4_bin'])]
    protected ?string $username = null;

    /**
     * E-mail.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $email = null;

    /**
     * Schválený.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $approved = true;

    /**
     * Jméno.
     */
    #[ORM\Column(type: 'string')]
    protected ?string $firstName = null;

    /**
     * Příjmení.
     */
    #[ORM\Column(type: 'string')]
    protected ?string $lastName = null;

    /**
     * Přezdívka.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $nickName = null;

    /**
     * Titul před jménem.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $degreePre = null;

    /**
     * Titul za jménem.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $degreePost = null;

    /**
     * Zobrazované jméno - Příjmení Jméno (Přezdívka).
     */
    #[ORM\Column(type: 'string')]
    protected string $displayName;

    /**
     * Zobrazované jméno lektora, včetně titulů.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $lectorName = null;

    /**
     * Bezpečnostní kód.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $securityCode = null;

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
    protected ?string $unit = null;

    /**
     * Pohlaví.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $sex = null;

    /**
     * Datum narození.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?DateTimeImmutable $birthdate = null;

    /**
     * Id uživatele ve skautIS.
     */
    #[ORM\Column(type: 'integer', unique: true, nullable: true, name: 'skautis_user_id')]
    protected ?int $skautISUserId = null;

    /**
     * Id osoby ve skautIS.
     */
    #[ORM\Column(type: 'integer', unique: true, nullable: true, name: 'skautis_person_id')]
    protected ?int $skautISPersonId = null;

    /**
     * Datum posledního přihlášení.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $lastLogin = null;

    /**
     * O mně.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $about = null;

    /**
     * Ulice.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $street = null;

    /**
     * Město.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $city = null;

    /**
     * Poštovní směrovací číslo.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $postcode = null;

    /**
     * Stát.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $state = null;

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
    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'user', cascade: ['persist'])]
    protected Collection $applications;

    /**
     * Přihlášené programy.
     *
     * @var Collection<int, ProgramApplication>
     */
    #[ORM\OneToMany(targetEntity: ProgramApplication::class, mappedBy: 'user', cascade: ['persist'])]
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
    protected ?string $paymentMethod = null;

    /**
     * Datum poslední platby.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?DateTimeImmutable $lastPaymentDate = null;

    /**
     * Datum a čas vytvoření přihlášky rolí.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $rolesApplicationDate = null;

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
    #[ORM\OneToMany(targetEntity: CustomInputValue::class, mappedBy: 'user', cascade: ['persist'])]
    protected Collection $customInputValues;

    /**
     * Neveřejná poznámka.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $note = null;

    /**
     * Fotka.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $photo = null;

    /**
     * Datum aktualizace fotky.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $photoUpdate = null;

    /**
     * Přihláška oddílu.
     */
    #[ORM\OneToOne(targetEntity: Troop::class, mappedBy: 'leader', cascade: ['persist'])]
    protected ?Troop $troop;

    /**
     * Telefon.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $phone = null;

    /**
     * Jméno matky.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $motherName = null;

    /**
     * Telefon matky.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $motherPhone = null;

    /**
     * Jméno otce.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $fatherName = null;

    /**
     * Telefon otce.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $fatherPhone = null;

    /**
     * Informace o zdravotním stavu - omezení, alergie, léky.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $healthInfo = null;

    /** @var Collection<int, UserGroupRole> */
    #[ORM\OneToMany(targetEntity: UserGroupRole::class, cascade: ['persist'], mappedBy: 'user')]
    protected Collection $groupRoles;

    public function __construct()
    {
        $this->applications                 = new ArrayCollection();
        $this->roles                        = new ArrayCollection();
        $this->programApplications          = new ArrayCollection();
        $this->lecturersBlocks              = new ArrayCollection();
        $this->notRegisteredMandatoryBlocks = new ArrayCollection();
        $this->customInputValues            = new ArrayCollection();
        $this->groupRoles                   = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
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

    public function getNickName(): ?string
    {
        return $this->nickName;
    }

    public function setNickName(?string $nickName): void
    {
        $this->nickName = $nickName;
        $this->updateDisplayName();
        $this->updateLectorName();
    }

    public function getDegreePre(): ?string
    {
        return $this->degreePre;
    }

    public function setDegreePre(?string $degreePre): void
    {
        $this->degreePre = $degreePre;
        $this->updateLectorName();
    }

    public function getDegreePost(): ?string
    {
        return $this->degreePost;
    }

    public function setDegreePost(?string $degreePost): void
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

    public function getLectorName(): ?string
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

    public function getSecurityCode(): ?string
    {
        return $this->securityCode;
    }

    public function setSecurityCode(?string $securityCode): void
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

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): void
    {
        $this->sex = $sex;
    }

    public function getBirthdate(): ?DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function setBirthdate(?DateTimeImmutable $birthdate): void
    {
        $this->birthdate = $birthdate;
    }

    public function getAge(): ?int
    {
        return $this->birthdate !== null ? (new DateTimeImmutable())->diff($this->birthdate)->y : null;
    }

    public function getSkautISUserId(): ?int
    {
        return $this->skautISUserId;
    }

    public function setSkautISUserId(?int $skautISUserId): void
    {
        $this->skautISUserId = $skautISUserId;
    }

    public function getSkautISPersonId(): ?int
    {
        return $this->skautISPersonId;
    }

    public function setSkautISPersonId(?int $skautISPersonId): void
    {
        $this->skautISPersonId = $skautISPersonId;
    }

    public function getLastLogin(): ?DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTimeImmutable $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): void
    {
        $this->about = $about;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): void
    {
        $this->postcode = $postcode;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * Vrátí adresu uživatele.
     */
    public function getAddress(): ?string
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

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @param Collection<int, Role> $roles
     */
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

    /**
     * @return Collection<int, Application>
     */
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
                Criteria::expr()->eq('state', ApplicationState::PAID)
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
                Criteria::expr()->eq('state', ApplicationState::WAITING_FOR_PAYMENT)
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
    public function getRolesApplication(): ?RolesApplication
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

    /**
     * @return Collection<int, Block>
     */
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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getLastPaymentDate(): ?DateTimeImmutable
    {
        return $this->lastPaymentDate;
    }

    public function setLastPaymentDate(?DateTimeImmutable $lastPaymentDate): void
    {
        $this->lastPaymentDate = $lastPaymentDate;
    }

    public function getRolesApplicationDate(): ?DateTimeImmutable
    {
        return $this->rolesApplicationDate;
    }

    public function setRolesApplicationDate(?DateTimeImmutable $rolesApplicationDate): void
    {
        $this->rolesApplicationDate = $rolesApplicationDate;
    }

    /**
     * @return Collection<int, Block>
     */
    public function getNotRegisteredMandatoryBlocks(): Collection
    {
        return $this->notRegisteredMandatoryBlocks;
    }

    public function getNotRegisteredMandatoryBlocksText(): string
    {
        return implode(', ', $this->notRegisteredMandatoryBlocks->map(static fn (Block $block) => $block->getName())->toArray());
    }

    /**
     * @param Collection<int, Block> $notRegisteredMandatoryBlocks
     */
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

    /**
     * @return Collection<int, CustomInputValue>
     */
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

    public function getCustomInputValue(CustomInput $customInput): ?CustomInputValue
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    public function getPhotoUpdate(): ?DateTimeImmutable
    {
        return $this->photoUpdate;
    }

    public function setPhotoUpdate(?DateTimeImmutable $photoUpdate): void
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
                    Criteria::expr()->eq('alternate', false)
                )
            )
        )->isEmpty();
    }

    public function isAlternate(Program $program): bool
    {
        return ! $this->programApplications->matching(
            Criteria::create()->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('program', $program),
                    Criteria::expr()->eq('alternate', true)
                )
            )
        )->isEmpty();
    }

    public function getTroop(): ?Troop
    {
        return $this->troop;
    }

    public function setTroop(?Troop $troop): void
    {
        $this->troop = $troop;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getMotherName(): ?string
    {
        return $this->motherName;
    }

    public function setMotherName(?string $motherName): void
    {
        $this->motherName = $motherName;
    }

    public function getMotherPhone(): ?string
    {
        return $this->motherPhone;
    }

    public function setMotherPhone(?string $motherPhone): void
    {
        $this->motherPhone = $motherPhone;
    }

    public function getFatherName(): ?string
    {
        return $this->fatherName;
    }

    public function setFatherName(?string $fatherName): void
    {
        $this->fatherName = $fatherName;
    }

    public function getFatherPhone(): ?string
    {
        return $this->fatherPhone;
    }

    public function setFatherPhone(?string $fatherPhone): void
    {
        $this->fatherPhone = $fatherPhone;
    }

    public function getHealthInfo(): ?string
    {
        return $this->healthInfo;
    }

    public function setHealthInfo(?string $healthInfo): void
    {
        $this->healthInfo = $healthInfo;
    }

    /**
     * @return Collection<int, UserGroupRole>
     */
    public function getGroupRoles(): Collection
    {
        return $this->groupRoles;
    }

    /**
     * Vrátí skupinové role uživatele oddělené čárkou.
     */
    public function getGroupRolesText(): string
    {
        $rolesNames = [];
        foreach ($this->groupRoles as $groupRole) {
            $troop = $groupRole->getTroop() ?? $groupRole->getPatrol()->getTroop();
            if ($troop->getState() !== TroopApplicationState::DRAFT) {
                $rolesNames[] = $groupRole->getRole()->getName();
            }
        }

        return implode(', ', $rolesNames);
    }
}
