<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * Entita platba.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="PaymentRepository")
 * @ORM\Table(name="payment")
 */
class Payment
{
    use Identifier;

    /**
     * Id platby v systému banky.
     * @ORM\Column(type="string", nullable=true)
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
    protected $ammount;

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


    public function __construct()
    {
        $this->programs = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getCapacity() : ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity) : void
    {
        $this->capacity = $capacity;
    }

    /**
     * @return Collection|Program[]
     */
    public function getPrograms() : Collection
    {
        return $this->programs;
    }
}
