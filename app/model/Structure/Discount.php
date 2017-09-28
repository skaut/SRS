<?php

namespace App\Model\Structure;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita sleva.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="DiscountRepository")
 * @ORM\Table(name="discount")
 */
class Discount
{
    const SUBEVENT_ID = 'subevent_id';
    const OPERATOR_OR = 'or';
    const OPERATOR_AND = 'and';
    const LEFT_PARENTHESIS = '(';
    const RIGHT_PARENTHESIS = ')';
    const END = 'end';


    use Identifier;

    /**
     * Podmínka.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $condition;

    /**
     * Sleva.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $discount;


    /**
     * Discount constructor.
     */
    public function __construct()
    {
        $this->conditionSubevents = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return int
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param int $discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }
}
