<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\Application\IncomeProof;
use App\Model\Application\VariableSymbol;
use App\Model\Enums\TroopApplicationState;
use App\Model\Payment\Payment;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use function md5;
use function mt_rand;
use function substr;
use function uniqid;

/**
 * Entita oddíl.
 */
#[ORM\Entity]
#[ORM\Table(name: 'troop')]
class Troop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    /**
     * Název oddílu.
     */
    #[ORM\Column(type: 'string')]
    protected string $name;

    /**
     * Družiny oddílu.
     *
     * @var Collection<int, Patrol>
     */
    #[ORM\OneToMany(mappedBy: 'troop', targetEntity: Patrol::class, cascade: ['persist'])]
    protected Collection $patrols;

    /**
     * Uživatelé.
     *
     * @var Collection<int, UserGroupRole>
     */
    #[ORM\OneToMany(mappedBy: 'troop', targetEntity: UserGroupRole::class, cascade: ['persist'])]
    protected Collection $usersRoles;

    /**
     * Poplatek za oddíl.
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
    protected ?DateTimeImmutable $maturityDate = null;

    /**
     * Platební metoda.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $paymentMethod = null;

    /**
     * Datum zaplacení.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?DateTimeImmutable $paymentDate = null;

    /**
     * Spárovaná platba.
     */
    #[ORM\ManyToOne(targetEntity: Payment::class, inversedBy: 'pairedTroops', cascade: ['persist'])]
    protected ?Payment $payment = null;

    /**
     * Příjmový doklad. Používá se pro generování id.
     */
    #[ORM\ManyToOne(targetEntity: IncomeProof::class, cascade: ['persist'])]
    protected ?IncomeProof $incomeProof = null;

    /**
     * Stav přihlášky.
     */
    #[ORM\Column(type: 'string')]
    protected string $state;

    /**
     * Vedoucí oddílu.
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'troop', cascade: ['persist'])]
    protected User $leader;

    /**
     * Kód pro párování oddílů.
     */
    #[ORM\Column(type: 'string')]
    protected string $pairingCode;

    /**
     * Spárovaný oddíl.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $pairedTroopCode = null;

    public function __construct(User $leader, VariableSymbol $variableSymbol)
    {
        $this->patrols    = new ArrayCollection();
        $this->usersRoles = new ArrayCollection();

        $this->leader         = $leader;
        $this->variableSymbol = $variableSymbol;

        $this->name            = 'S-' . $variableSymbol->getVariableSymbol();
        $this->pairingCode     = substr(md5(uniqid((string) mt_rand(), true)), 0, 20);
        $this->applicationDate = new DateTimeImmutable();
        $this->state           = TroopApplicationState::DRAFT;
        $this->fee             = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<int, Patrol>
     */
    public function getPatrols(): Collection
    {
        return $this->patrols;
    }

    /**
     * @return Collection<int, UserGroupRole>
     */
    public function getUsersRoles(): Collection
    {
        return $this->usersRoles;
    }

    public function getFee(): int
    {
        return $this->fee;
    }

    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    public function getVariableSymbol(): VariableSymbol
    {
        return $this->variableSymbol;
    }

    public function getApplicationDate(): DateTimeImmutable
    {
        return $this->applicationDate;
    }

    public function getMaturityDate(): ?DateTimeImmutable
    {
        return $this->maturityDate;
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getLeader(): User
    {
        return $this->leader;
    }

    public function getPairingCode(): string
    {
        return $this->pairingCode;
    }

    public function getPairedTroopCode(): ?string
    {
        return $this->pairedTroopCode;
    }

    public function setPairedTroopCode(?string $pairedTroopCode): void
    {
        $this->pairedTroopCode = $pairedTroopCode;
    }
}
