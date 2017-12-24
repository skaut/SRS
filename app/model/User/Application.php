<?php

namespace App\Model\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita přihláška.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="ApplicationRepository")
 * @ORM\Table(name="application")
 */
class Application
{
    use Identifier;

    /**
     * Uživatel.
     * @ORM\ManyToOne(targetEntity="User", inversedBy="applications", cascade={"persist"})
     * @var User
     */
    protected $user;

    /**
     * Role.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * Podakce.
     * @ORM\ManyToMany(targetEntity="\App\Model\Structure\Subevent", inversedBy="applications", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $subevents;

    /**
     * Poplatek.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $fee;

    /**
     * Variabilní symbol.
     * @ORM\ManyToOne(targetEntity="VariableSymbol", cascade={"persist"})
     * @var VariableSymbol
     */
    protected $variableSymbol;

    /**
     * Datum podání přihlášky.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $applicationDate;

    /**
     * Datum splatnosti.
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    protected $maturityDate;

    /**
     * Platební metoda.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $paymentMethod;

    /**
     * Datum zaplacení.
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    protected $paymentDate;

    /**
     * Datum vytištění dokladu o zaplacení.
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    protected $incomeProofPrintedDate;

    /**
     * Stav přihlášky.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $state;

    /**
     * První přihláška uživatele.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $first = TRUE;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @var User
     */
    protected $createdBy;

    /**
     * Platnost záznamu od.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $validFrom;

    /**
     * Platnost záznamu do.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $validTo;


    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->subevents = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return ArrayCollection
     */
    public function getSubevents()
    {
        return $this->subevents;
    }

    /**
     * @param ArrayCollection $subevents
     */
    public function setSubevents($subevents)
    {
        $this->subevents = $subevents;
    }

    /**
     * Vrací podakce oddělené čárkou.
     * @return string
     */
    public function getSubeventsText()
    {
        $subeventsNames = [];
        foreach ($this->subevents as $subevent)
            $subeventsNames[] = $subevent->getName();
        return implode(', ', $subeventsNames);
    }

    /**
     * @return int
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Vrací poplatek slovy.
     * @return mixed|string
     */
    public function getFeeWords()
    {
        $numbersWords = new \Numbers_Words();
        $feeWord = $numbersWords->toWords($this->getFee(), 'cs');
        $feeWord = iconv('windows-1250', 'UTF-8', $feeWord);
        $feeWord = str_replace(" ", "", $feeWord);
        return $feeWord;
    }

    /**
     * @param int $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }

    /**
     * @return VariableSymbol|null
     */
    public function getVariableSymbol(): ?VariableSymbol
    {
        return $this->variableSymbol;
    }

    /**
     * @param VariableSymbol $variableSymbol
     */
    public function setVariableSymbol(VariableSymbol $variableSymbol)
    {
        $this->variableSymbol = $variableSymbol;
    }

    /**
     * @return \DateTime
     */
    public function getApplicationDate()
    {
        return $this->applicationDate;
    }

    /**
     * @param \DateTime $applicationDate
     */
    public function setApplicationDate($applicationDate)
    {
        $this->applicationDate = $applicationDate;
    }

    /**
     * @return \DateTime
     */
    public function getMaturityDate()
    {
        return $this->maturityDate;
    }

    /**
     * @param \DateTime $maturityDate
     */
    public function setMaturityDate($maturityDate)
    {
        $this->maturityDate = $maturityDate;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return \DateTime
     */
    public function getPaymentDate()
    {
        return $this->paymentDate;
    }

    /**
     * @param \DateTime $paymentDate
     */
    public function setPaymentDate($paymentDate)
    {
        $this->paymentDate = $paymentDate;
    }

    /**
     * @return \DateTime
     */
    public function getIncomeProofPrintedDate()
    {
        return $this->incomeProofPrintedDate;
    }

    /**
     * @param \DateTime $incomeProofPrintedDate
     */
    public function setIncomeProofPrintedDate($incomeProofPrintedDate)
    {
        $this->incomeProofPrintedDate = $incomeProofPrintedDate;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function isFirst()
    {
        return $this->first;
    }

    /**
     * @param mixed $first
     */
    public function setFirst($first)
    {
        $this->first = $first;
    }

    /**
     * @return User
     */
    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    /**
     * @param User $createdBy
     */
    public function setCreatedBy(User $createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return \DateTime
     */
    public function getValidFrom(): \DateTime
    {
        return $this->validFrom;
    }

    /**
     * @param \DateTime $validFrom
     */
    public function setValidFrom(\DateTime $validFrom)
    {
        $this->validFrom = $validFrom;
    }

    /**
     * @return \DateTime
     */
    public function getValidTo(): \DateTime
    {
        return $this->validTo;
    }

    /**
     * @param \DateTime $validTo
     */
    public function setValidTo(\DateTime $validTo)
    {
        $this->validTo = $validTo;
    }
}
