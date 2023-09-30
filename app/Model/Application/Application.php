<?php

declare(strict_types=1);

namespace App\Model\Application;

use App\Model\Acl\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Payment\Payment;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use App\Utils\Helpers;
use DateTimeImmutable;
use Defr\QRPlatba\QRPlatba;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Numbers_Words;

use function implode;
use function str_replace;

/**
 * Abstraktní entita přihláška.
 */
#[ORM\Entity]
#[ORM\Table(name: 'application')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'roles_application' => RolesApplication::class,
    'subevents_application' => SubeventsApplication::class,
])]
abstract class Application
{
    /**
     * Přihláška rolí.
     */
    public const ROLES = 'roles';

    /**
     * Přihláška na podakce.
     */
    public const SUBEVENTS = 'subevents';

    /**
     * Typ přihlášky.
     */
    protected string $type;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Id přihlášky.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected int|null $applicationId = null;

    /**
     * Uživatel.
     */
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'applications')]
    protected User $user;

    /**
     * Role.
     *
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    protected Collection $roles;

    /**
     * Podakce.
     *
     * @var Collection<int, Subevent>
     */
    #[ORM\ManyToMany(targetEntity: Subevent::class, inversedBy: 'applications', cascade: ['persist'])]
    protected Collection $subevents;

    /**
     * Poplatek.
     */
    #[ORM\Column(type: 'integer')]
    protected int $fee;

    /**
     * Variabilní symbol.
     */
    #[ORM\ManyToOne(targetEntity: VariableSymbol::class, cascade: ['persist'])]
    protected VariableSymbol $variableSymbol;

    /**
     * Datum podání přihlášky.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $applicationDate;

    /**
     * Datum splatnosti.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected DateTimeImmutable|null $maturityDate = null;

    /**
     * Platební metoda.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $paymentMethod = null;

    /**
     * Datum zaplacení.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected DateTimeImmutable|null $paymentDate = null;

    /**
     * Spárovaná platba.
     */
    #[ORM\ManyToOne(targetEntity: Payment::class, cascade: ['persist'], inversedBy: 'pairedApplications')]
    protected Payment|null $payment = null;

    /**
     * Příjmový doklad. Používá se pro generování id.
     */
    #[ORM\ManyToOne(targetEntity: IncomeProof::class, cascade: ['persist'])]
    protected IncomeProof|null $incomeProof = null;

    /**
     * Stav přihlášky.
     */
    #[ORM\Column(type: 'string')]
    protected string|null $state = null;

    /**
     * Uživatel, který vytvořil přihlášku.
     */
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    protected User|null $createdBy = null;

    /**
     * Platnost záznamu od.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $validFrom;

    /**
     * Platnost záznamu do.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable|null $validTo = null;

    public function __construct(User $user)
    {
        $this->user      = $user;
        $this->roles     = new ArrayCollection();
        $this->subevents = new ArrayCollection();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->roles     = clone $this->roles;
            $this->subevents = clone $this->subevents;
        }
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getApplicationId(): int
    {
        return $this->applicationId;
    }

    public function setApplicationId(int $applicationId): void
    {
        $this->applicationId = $applicationId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /** @return Collection<int, Role> */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * Vrací názvy rolí oddělené čárkou.
     */
    public function getRolesText(): string
    {
        return implode(', ', $this->roles->map(static fn (Role $role) => $role->getName())->toArray());
    }

    /** @return Collection<int, Subevent> */
    public function getSubevents(): Collection
    {
        return $this->subevents;
    }

    /**
     * Vrací názvy podakcí oddělené čárkou.
     */
    public function getSubeventsText(): string
    {
        return implode(', ', $this->subevents->map(static fn (Subevent $subevent) => $subevent->getName())->toArray());
    }

    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * Vrací poplatek slovy.
     */
    public function getFeeWords(): string
    {
        $numbersWords = new Numbers_Words();
        $feeWord      = $numbersWords->toWords($this->getFee(), 'cs');

        return str_replace(' ', '', $feeWord);
    }

    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    public function getVariableSymbol(): VariableSymbol
    {
        return $this->variableSymbol;
    }

    /**
     * Vrací text variabilního symbolu.
     */
    public function getVariableSymbolText(): string
    {
        return $this->variableSymbol->getVariableSymbol();
    }

    public function setVariableSymbol(VariableSymbol $variableSymbol): void
    {
        $this->variableSymbol = $variableSymbol;
    }

    public function getApplicationDate(): DateTimeImmutable
    {
        return $this->applicationDate;
    }

    public function setApplicationDate(DateTimeImmutable $applicationDate): void
    {
        $this->applicationDate = $applicationDate;
    }

    public function getMaturityDate(): DateTimeImmutable|null
    {
        return $this->maturityDate;
    }

    /**
     * Vrací datum splastnosti jako text.
     */
    public function getMaturityDateText(): string|null
    {
        return $this->maturityDate?->format(Helpers::DATE_FORMAT);
    }

    public function setMaturityDate(DateTimeImmutable|null $maturityDate): void
    {
        $this->maturityDate = $maturityDate;
    }

    public function getPaymentMethod(): string|null
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string|null $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentDate(): DateTimeImmutable|null
    {
        return $this->paymentDate;
    }

    /**
     * Vrací datum platby jako text.
     */
    public function getPaymentDateText(): string|null
    {
        return $this->paymentDate?->format(Helpers::DATE_FORMAT);
    }

    public function setPaymentDate(DateTimeImmutable|null $paymentDate): void
    {
        $this->paymentDate = $paymentDate;
    }

    public function getPayment(): Payment|null
    {
        return $this->payment;
    }

    public function setPayment(Payment|null $payment): void
    {
        $this->payment?->removePairedApplication($this);

        $payment?->addPairedApplication($this);

        $this->payment = $payment;
    }

    public function getIncomeProof(): IncomeProof|null
    {
        return $this->incomeProof;
    }

    public function setIncomeProof(IncomeProof|null $incomeProof): void
    {
        $this->incomeProof = $incomeProof;
    }

    public function getState(): string|null
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getCreatedBy(): User|null
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User|null $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getValidFrom(): DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(DateTimeImmutable $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo(): DateTimeImmutable|null
    {
        return $this->validTo;
    }

    public function setValidTo(DateTimeImmutable|null $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function isValid(): bool
    {
        return $this->validTo === null;
    }

    public function isCanceled(): bool
    {
        return $this->state === ApplicationState::CANCELED || $this->state === ApplicationState::CANCELED_NOT_PAID;
    }

    public function isPaid(): bool
    {
        return $this->state === ApplicationState::PAID || $this->getState() === ApplicationState::PAID_FREE;
    }

    public function isWaitingForPayment(): bool
    {
        return $this->state === ApplicationState::WAITING_FOR_PAYMENT;
    }

    public function getPaymentQr(string $accountNumber, string $message): string
    {
        $qrPlatba = new QRPlatba();

        $qrPlatba->setAccount($accountNumber)
            ->setVariableSymbol($this->getVariableSymbolText())
            ->setAmount($this->fee)
            ->setMessage($message);

        return $qrPlatba->getQRCodeImage(true, 100);
    }
}
