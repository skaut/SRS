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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDiscountCondition(): string
    {
        return $this->discountCondition;
    }

    /**
     * @param string $discountCondition
     */
    public function setDiscountCondition(string $discountCondition): void
    {
        $this->discountCondition = $discountCondition;
    }

    /**
     * @return int
     */
    public function getDiscount(): int
    {
        return $this->discount;
    }

    /**
     * @param int $discount
     */
    public function setDiscount(int $discount): void
    {
        $this->discount = $discount;
    }
}
