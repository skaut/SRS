<?php

namespace App\Services;

use App\Model\Structure\Discount;
use App\Model\Structure\DiscountRepository;
use InvalidArgumentException;
use Nette;


/**
 * Služba pro správu slev.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountService extends Nette\Object
{
    /**
     * Tokeny podmínky.
     * @var string[]
     */
    private $symbols;

    /**
     * Aktuálně zpracovávaný token.
     * @var int
     */
    private $currentSymbol = 0;

    /**
     * Id zvolených podakcí.
     * @var int[]
     */
    private $selectedSubeventsIds;

    /** @var DiscountRepository */
    private $discountRepository;


    /**
     * DiscountService constructor.
     * @param DiscountRepository $discountRepository
     */
    public function __construct(DiscountRepository $discountRepository)
    {
        $this->discountRepository = $discountRepository;
    }

    /**
     * Vypočítá slevu pro kombinaci podakcí.
     * @param $selectedSubeventsIds
     * @return int
     */
    public function countDiscount($selectedSubeventsIds) {
        $totalDiscount = 0;

        foreach ($this->discountRepository->findAll() as $discount) {
            $tokens = explode('|', $discount->getCondition());

            $this->symbols = [];
            foreach ($tokens as $token) {
                if (is_numeric($token))
                    $this->symbols[] = ['symbol' => Discount::SUBEVENT_ID, 'value' => $token];
                else
                    $this->symbols[] = ['symbol' => $token];
            }

            $this->symbols[] = ['symbol' => Discount::END];

            $this->selectedSubeventsIds = $selectedSubeventsIds;

            $this->parseExpression($result);

            if ($result)
                $totalDiscount += $discount->getDiscount();
        }

        return $totalDiscount;
    }

    /**
     * Ověří formát podmínky pro slevu.
     * @param $condition
     * @return bool
     */
    public function validateDiscountCondition($condition) {
        $tokens = explode('|', $condition);

        $this->symbols = [];
        foreach ($tokens as $token) {
            if (is_int($token))
                $this->symbols[] = ['symbol' => Discount::SUBEVENT_ID, 'value' => $token];
            else
                $this->symbols[] = ['symbol' => $token];
        }

        $this->symbols[] = ['symbol' => Discount::END];

        $this->selectedSubeventsIds = [];

        try {
            $this->parseExpression($result);
        }
        catch (InvalidArgumentException $exception) {
            return FALSE;
        }

        return TRUE;
    }

    private function nextSymbol() {
        $this->currentSymbol++;
    }

    private function symbol() {
        return $this->symbols[$this->currentSymbol]['symbol'];
    }

    private function symbolValue() {
        return $this->symbols[$this->currentSymbol]['value'];
    }

    private function accept($symbol) {
        if ($this->symbol() == $symbol)
            $this->nextSymbol();
        else
            throw new InvalidArgumentException;
    }

    private function acceptSubevent(&$sValue) {
        if ($this->symbol() == Discount::SUBEVENT_ID) {
            $sValue = in_array($this->symbolValue(), $this->selectedSubeventsIds) ? 1 : 0;
            $this->nextSymbol();
        }
        else
            throw new InvalidArgumentException;
    }

    private function parseExpression(&$sValue) {
        switch ($this->symbol()) {
            case Discount::SUBEVENT_ID:
            case Discount::LEFT_PARENTHESIS:
                $this->parseTerm($termSValue);
                $this->parseExpression_($termSValue, $expressionSValue);
                $sValue = $expressionSValue;
                break;

            default:
                throw new InvalidArgumentException;
        }
    }

    private function parseExpression_($dValue, &$sValue) {
        switch ($this->symbol()) {
            case Discount::OPERATOR_OR:
                $this->accept(Discount::OPERATOR_OR);
                $this->parseTerm($termSValue);
                $this->parseExpression_($dValue | $termSValue, $expressionSValue);
                $sValue = $expressionSValue;
                break;

            case Discount::RIGHT_PARENTHESIS:
            case Discount::END:
                $sValue = $dValue;
                break;

            default:
                throw new InvalidArgumentException;
        }
    }

    private function parseTerm(&$sValue) {
        switch ($this->symbol()) {
            case Discount::SUBEVENT_ID:
            case Discount::LEFT_PARENTHESIS:
                $this->parseFactor($factorSValue);
                $this->parseTerm_($factorSValue, $termSValue);
                $sValue = $termSValue;
                break;

            default:
                throw new InvalidArgumentException;
        }
    }

    private function parseTerm_($dValue, &$sValue) {
        switch ($this->symbol()) {
            case Discount::OPERATOR_AND:
                $this->accept(Discount::OPERATOR_AND);
                $this->parseFactor($factorSValue);
                $this->parseTerm_($dValue & $factorSValue, $termSValue);
                $sValue = $termSValue;
                break;

            case Discount::OPERATOR_OR:
            case Discount::RIGHT_PARENTHESIS:
            case Discount::END:
                $sValue = $dValue;
                break;

            default:
                throw new InvalidArgumentException;
        }
    }

    private function parseFactor(&$sValue) {
        switch ($this->symbol()) {
            case Discount::SUBEVENT_ID:
                $this->acceptSubevent($sValue);
                break;

            case Discount::LEFT_PARENTHESIS:
                $this->accept(Discount::LEFT_PARENTHESIS);
                $this->parseExpression($sValue);
                $this->accept(Discount::RIGHT_PARENTHESIS);
                break;

            default:
                throw new InvalidArgumentException;
        }
    }
}
