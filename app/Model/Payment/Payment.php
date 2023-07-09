<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Application\Application;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

use function array_key_exists;
use function array_keys;
use function implode;

/**
 * Entita platba.
 */
#[ORM\Entity]
#[ORM\Table(name: 'payment')]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Id platby v systému banky.
     */
    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    protected string|null $transactionId = null;

    /**
     * Datum platby.
     */
    #[ORM\Column(type: 'date_immutable')]
    protected DateTimeImmutable $date;

    /**
     * Částka.
     */
    #[ORM\Column(type: 'float')]
    protected float $amount;

    /**
     * Číslo protiúčtu.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $accountNumber = null;

    /**
     * Majitel protiúčtu.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $accountName = null;

    /**
     * Variabilní symbol platby.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $variableSymbol = null;

    /**
     * Zpráva pro příjemce.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $message = null;

    /**
     * Spárované přihlášky.
     *
     * @var Collection<int, Application>
     */
    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'payment', cascade: ['persist'])]
    protected Collection $pairedApplications;

    /**
     * Stav platby.
     */
    #[ORM\Column(type: 'string')]
    protected string $state;

    public function __construct()
    {
        $this->pairedApplications = new ArrayCollection();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getTransactionId(): string|null
    {
        return $this->transactionId;
    }

    public function setTransactionId(string|null $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getAccountNumber(): string|null
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string|null $accountNumber): void
    {
        $this->accountNumber = $accountNumber;
    }

    public function getAccountName(): string|null
    {
        return $this->accountName;
    }

    public function setAccountName(string|null $accountName): void
    {
        $this->accountName = $accountName;
    }

    public function getVariableSymbol(): string|null
    {
        return $this->variableSymbol;
    }

    public function setVariableSymbol(string|null $variableSymbol): void
    {
        $this->variableSymbol = $variableSymbol;
    }

    public function getMessage(): string|null
    {
        return $this->message;
    }

    public function setMessage(string|null $message): void
    {
        $this->message = $message;
    }

    /** @return Collection<int, Application> */
    public function getPairedApplications(): Collection
    {
        return $this->pairedApplications;
    }

    public function addPairedApplication(Application $application): void
    {
        if (! $this->pairedApplications->contains($application)) {
            $this->pairedApplications->add($application);
            $application->setPayment($this);
        }
    }

    public function removePairedApplication(Application $application): void
    {
        if ($this->pairedApplications->contains($application)) {
            $this->pairedApplications->removeElement($application);
            $application->setPayment(null);
        }
    }

    /** @return Collection<int, Application> */
    public function getPairedValidApplications(): Collection
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->isNull('validTo'),
        );

        return $this->pairedApplications->matching($criteria);
    }

    public function getPairedValidApplicationsText(): string
    {
        $usersVS    = [];
        $usersNames = [];
        foreach ($this->getPairedValidApplications() as $pairedApplication) {
            $userId = $pairedApplication->getUser()->getId();
            if (! array_key_exists($userId, $usersNames)) {
                $usersVS[$userId]    = [];
                $usersNames[$userId] = $pairedApplication->getUser()->getLastName() . ' ' . $pairedApplication->getUser()->getFirstName();
            }

            $usersVS[$userId][] = $pairedApplication->getVariableSymbolText();
        }

        $usersTexts = [];
        foreach (array_keys($usersNames) as $userId) {
            $usersTexts[] = $usersNames[$userId] . ' (' . implode(', ', $usersVS[$userId]) . ')';
        }

        return implode(', ', $usersTexts);
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }
}
