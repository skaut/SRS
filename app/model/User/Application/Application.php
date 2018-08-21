<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\ACL\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Structure\Subevent;
use App\Utils\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use function implode;
use function str_replace;

/**
 * Abstraktní entita přihláška.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="ApplicationRepository")
 * @ORM\Table(name="application")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "roles_application" = "RolesApplication",
 *     "subevents_application" = "SubeventsApplication"
 * })
 */
abstract class Application
{
    /**
     * Přihláška rolí.
     * @var string
     */
    public const ROLES = 'roles';

    /**
     * Přihláška na podakce.
     * @var string
     */
    public const SUBEVENTS = 'subevents';

    /**
     * Typ přihlášky.
     * @var string
     */
    protected $type;

    use Identifier;

    /**
     * Id přihlášky.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $applicationId;

    /**
     * Uživatel.
     * @ORM\ManyToOne(targetEntity="User", inversedBy="applications", cascade={"persist"})
     * @var User
     */
    protected $user;

    /**
     * Role.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", cascade={"persist"})
     * @var Collection|Role[]
     */
    protected $roles;

    /**
     * Podakce.
     * @ORM\ManyToMany(targetEntity="\App\Model\Structure\Subevent", inversedBy="applications", cascade={"persist"})
     * @var Collection|Subevent[]
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
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $validTo;


    public function __construct()
    {
        $this->roles     = new ArrayCollection();
        $this->subevents = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getApplicationId() : int
    {
        return $this->applicationId;
    }

    public function setApplicationId(int $applicationId) : void
    {
        $this->applicationId = $applicationId;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function setUser(User $user) : void
    {
        $this->user = $user;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles() : Collection
    {
        return $this->roles;
    }

    /**
     * Vrací názvy rolí oddělené čárkou.
     */
    public function getRolesText() : string
    {
        return implode(', ', $this->roles->map(function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @return Collection|Subevent[]
     */
    public function getSubevents() : Collection
    {
        return $this->subevents;
    }

    /**
     * Vrací názvy podakcí oddělené čárkou.
     */
    public function getSubeventsText() : string
    {
        return implode(', ', $this->subevents->map(function (Subevent $subevent) {
            return $subevent->getName();
        })->toArray());
    }

    public function getFee() : int
    {
        return $this->fee;
    }

    /**
     * Vrací poplatek slovy.
     */
    public function getFeeWords() : string
    {
        $numbersWords = new \Numbers_Words();
        $feeWord      = $numbersWords->toWords($this->getFee(), 'cs');
        $feeWord      = str_replace(' ', '', $feeWord);
        return $feeWord;
    }

    public function setFee(int $fee) : void
    {
        $this->fee = $fee;
    }

    public function getVariableSymbol() : VariableSymbol
    {
        return $this->variableSymbol;
    }

    /**
     * Vrací text variabilního symbolu.
     */
    public function getVariableSymbolText() : string
    {
        return $this->variableSymbol->getVariableSymbol();
    }

    public function setVariableSymbol(VariableSymbol $variableSymbol) : void
    {
        $this->variableSymbol = $variableSymbol;
    }

    public function getApplicationDate() : \DateTime
    {
        return $this->applicationDate;
    }

    public function setApplicationDate(\DateTime $applicationDate) : void
    {
        $this->applicationDate = $applicationDate;
    }

    public function getMaturityDate() : ?\DateTime
    {
        return $this->maturityDate;
    }

    /**
     * Vrací datum splastnosti jako text.
     */
    public function getMaturityDateText() : ?string
    {
        return $this->maturityDate !== null ? $this->maturityDate->format(Helpers::DATE_FORMAT) : null;
    }

    public function setMaturityDate(?\DateTime $maturityDate) : void
    {
        $this->maturityDate = $maturityDate;
    }

    public function getPaymentMethod() : ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod) : void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentDate() : ?\DateTime
    {
        return $this->paymentDate;
    }

    /**
     * Vrací datum platby jako text.
     */
    public function getPaymentDateText() : ?string
    {
        return $this->paymentDate !== null ? $this->paymentDate->format(Helpers::DATE_FORMAT) : null;
    }

    public function setPaymentDate(?\DateTime $paymentDate) : void
    {
        $this->paymentDate = $paymentDate;
    }

    public function getIncomeProofPrintedDate() : ?\DateTime
    {
        return $this->incomeProofPrintedDate;
    }

    /**
     * Vrací datum vytištění dokladu jako text.
     */
    public function getIncomeProofPrintedDateText() : ?string
    {
        return $this->incomeProofPrintedDate !== null ? $this->incomeProofPrintedDate->format(Helpers::DATE_FORMAT) : null;
    }

    public function setIncomeProofPrintedDate(?\DateTime $incomeProofPrintedDate) : void
    {
        $this->incomeProofPrintedDate = $incomeProofPrintedDate;
    }

    public function getState() : ?string
    {
        return $this->state;
    }

    public function setState(?string $state) : void
    {
        $this->state = $state;
    }

    public function getCreatedBy() : ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy) : void
    {
        $this->createdBy = $createdBy;
    }

    public function getValidFrom() : \DateTime
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTime $validFrom) : void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo() : ?\DateTime
    {
        return $this->validTo;
    }

    public function setValidTo(?\DateTime $validTo) : void
    {
        $this->validTo = $validTo;
    }

    public function isCanceled() : bool
    {
        return $this->state === ApplicationState::CANCELED || $this->state === ApplicationState::CANCELED_NOT_PAID;
    }
}
