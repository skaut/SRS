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
use Nettrine\ORM\Entity\Attributes\Id;
use Numbers_Words;

use function implode;
use function str_replace;

/**
 * Abstraktní entita přihláška.
 *
 * @ORM\Entity
 * @ORM\Table(name="application")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "roles_application" = "RolesApplication",
 *     "subevents_application" = "SubeventsApplication"
 * })
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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
    use Id;

    /**
     * Id přihlášky.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $applicationId = null;

    /**
     * Uživatel.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", inversedBy="applications", cascade={"persist"})
     */
    protected User $user;

    /**
     * Role.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role", cascade={"persist"})
     *
     * @var Collection|Role[]
     */
    protected Collection $roles;

    /**
     * Podakce.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Structure\Subevent", inversedBy="applications", cascade={"persist"})
     *
     * @var Collection|Subevent[]
     */
    protected Collection $subevents;

    /**
     * Poplatek.
     *
     * @ORM\Column(type="integer")
     */
    protected int $fee;

    /**
     * Variabilní symbol.
     *
     * @ORM\ManyToOne(targetEntity="VariableSymbol", cascade={"persist"})
     */
    protected VariableSymbol $variableSymbol;

    /**
     * Datum podání přihlášky.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    protected DateTimeImmutable $applicationDate;

    /**
     * Datum splatnosti.
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $maturityDate = null;

    /**
     * Platební metoda.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $paymentMethod = null;

    /**
     * Datum zaplacení.
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $paymentDate = null;

    /**
     * Spárovaná platba.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\Payment\Payment", inversedBy="pairedApplications", cascade={"persist"})
     */
    protected ?Payment $payment = null;

    /**
     * Příjmový doklad. Používá se pro generování id.
     *
     * @ORM\ManyToOne(targetEntity="IncomeProof", cascade={"persist"})
     */
    protected ?IncomeProof $incomeProof = null;

    /**
     * Stav přihlášky.
     *
     * @ORM\Column(type="string")
     */
    protected ?string $state = null;

    /**
     * Uživatel, který vytvořil přihlášku.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     */
    protected ?User $createdBy = null;

    /**
     * Platnost záznamu od.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    protected DateTimeImmutable $validFrom;

    /**
     * Platnost záznamu do.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $validTo = null;

    public function __construct()
    {
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

    public function getId(): int
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        if (! $user->getApplications()->contains($this)) {
            $user->addApplication($this);
        }
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * Vrací názvy rolí oddělené čárkou.
     */
    public function getRolesText(): string
    {
        return implode(', ', $this->roles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @return Collection|Subevent[]
     */
    public function getSubevents(): Collection
    {
        return $this->subevents;
    }

    /**
     * Vrací názvy podakcí oddělené čárkou.
     */
    public function getSubeventsText(): string
    {
        return implode(', ', $this->subevents->map(static function (Subevent $subevent) {
            return $subevent->getName();
        })->toArray());
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
        $feeWord      = str_replace(' ', '', $feeWord);

        return $feeWord;
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

    public function getMaturityDate(): ?DateTimeImmutable
    {
        return $this->maturityDate;
    }

    /**
     * Vrací datum splastnosti jako text.
     */
    public function getMaturityDateText(): ?string
    {
        return $this->maturityDate !== null ? $this->maturityDate->format(Helpers::DATE_FORMAT) : null;
    }

    public function setMaturityDate(?DateTimeImmutable $maturityDate): void
    {
        $this->maturityDate = $maturityDate;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentDate(): ?DateTimeImmutable
    {
        return $this->paymentDate;
    }

    /**
     * Vrací datum platby jako text.
     */
    public function getPaymentDateText(): ?string
    {
        return $this->paymentDate !== null ? $this->paymentDate->format(Helpers::DATE_FORMAT) : null;
    }

    public function setPaymentDate(?DateTimeImmutable $paymentDate): void
    {
        $this->paymentDate = $paymentDate;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): void
    {
        $this->payment = $payment;
    }

    public function getIncomeProof(): ?IncomeProof
    {
        return $this->incomeProof;
    }

    public function setIncomeProof(?IncomeProof $incomeProof): void
    {
        $this->incomeProof = $incomeProof;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): void
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

    public function getValidTo(): ?DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(?DateTimeImmutable $validTo): void
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
        return $this->state == ApplicationState::PAID || $this->getState() == ApplicationState::PAID_FREE;
    }

    public function isWaitingForPayment(): bool
    {
        return $this->state == ApplicationState::WAITING_FOR_PAYMENT;
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
