<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\User\Application;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;
use function array_key_exists;
use function array_keys;
use function implode;

/**
 * Entita platba.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity(repositoryClass="PaymentRepository")
 * @ORM\Table(name="payment")
 */
class Payment
{
    use Identifier;

    /**
     * Id platby v systému banky.
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @var string
     */
    protected $transactionId;

    /**
     * Datum platby.
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    protected $date;

    /**
     * Částka.
     * @ORM\Column(type="float")
     * @var double
     */
    protected $amount;

    /**
     * Číslo protiúčtu.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $accountNumber;

    /**
     * Majitel protiúčtu.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $accountName;

    /**
     * Variabilní symbol platby.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $variableSymbol;

    /**
     * Zpráva pro příjemce.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $message;

    /**
     * Spárované přihlášky.
     * @ORM\OneToMany(targetEntity="\App\Model\User\Application", mappedBy="payment", cascade={"persist"})
     * @var Collection|Application[]
     */
    protected $pairedApplications;

    /**
     * Stav platby.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $state;


    public function __construct()
    {
        $this->pairedApplications = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getTransactionId() : ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId) : void
    {
        $this->transactionId = $transactionId;
    }

    public function getDate() : \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date) : void
    {
        $this->date = $date;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function setAmount(float $amount) : void
    {
        $this->amount = $amount;
    }

    public function getAccountNumber() : ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber) : void
    {
        $this->accountNumber = $accountNumber;
    }

    public function getAccountName() : ?string
    {
        return $this->accountName;
    }

    public function setAccountName(?string $accountName) : void
    {
        $this->accountName = $accountName;
    }

    public function getVariableSymbol() : ?string
    {
        return $this->variableSymbol;
    }

    public function setVariableSymbol(?string $variableSymbol) : void
    {
        $this->variableSymbol = $variableSymbol;
    }

    public function getMessage() : ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message) : void
    {
        $this->message = $message;
    }

    /**
     * @return Application[]|Collection
     */
    public function getPairedApplications() : Collection
    {
        return $this->pairedApplications;
    }

    /**
     * @return Application[]|Collection
     */
    public function getPairedValidApplications() : Collection
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->isNull('validTo')
        );
        return $this->pairedApplications->matching($criteria);
    }

    public function getPairedValidApplicationsText() : string
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

    public function getState() : string
    {
        return $this->state;
    }

    public function setState(string $state) : void
    {
        $this->state = $state;
    }
}
