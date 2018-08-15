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
    public const SUBEVENT_ID       = 'subevent_id';
    public const OPERATOR_OR       = 'or';
    public const OPERATOR_AND      = 'and';
    public const LEFT_PARENTHESIS  = '(';
    public const RIGHT_PARENTHESIS = ')';
    public const END               = '';


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


    public function getId() : int
    {
        return $this->id;
    }

    public function getDiscountCondition() : string
    {
        return $this->discountCondition;
    }

    public function setDiscountCondition(string $discountCondition) : void
    {
        $this->discountCondition = $discountCondition;
    }

    public function getDiscount() : int
    {
        return $this->discount;
    }

    public function setDiscount(int $discount) : void
    {
        $this->discount = $discount;
    }
}
