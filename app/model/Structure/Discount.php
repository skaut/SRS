<?php
declare(strict_types=1);

namespace App\Model\Structure;

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
    const END = '';


    use Identifier;

    /**
     * Podmínka.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $discountCondition;

    /**
     * Sleva.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $discount;


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
    public function getDiscountCondition()
    {
        return $this->discountCondition;
    }

    /**
     * @param string $discountCondition
     */
    public function setDiscountCondition($discountCondition)
    {
        $this->discountCondition = $discountCondition;
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
