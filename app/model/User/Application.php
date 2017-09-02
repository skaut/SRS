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
     * Podakce.
     * @ORM\ManyToMany(targetEntity="\App\Model\Structure\Subevent", inversedBy="applications", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $subevents;

    /**
     * Datum podání přihlášky.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $applicationDate;

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
     * Stav přihlášky.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $state;


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
}
