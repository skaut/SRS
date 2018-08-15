<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Structure\Discount;
use App\Model\Structure\DiscountRepository;
use App\Model\Structure\SubeventRepository;
use InvalidArgumentException;
use Kdyby\Translation\Translator;
use Nette;
use function explode;
use function in_array;
use function is_numeric;

/**
 * Služba pro správu slev.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountService
{
    use Nette\SmartObject;

    /**
     * Tokeny podmínky.
     * @var array
     */
    private $symbols;

    /**
     * Aktuálně zpracovávaný token.
     * @var int
     */
    private $currentSymbol;

    /**
     * Id zvolených podakcí.
     * @var int[]
     */
    private $selectedSubeventsIds;

    /** @var DiscountRepository */
    private $discountRepository;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var Translator */
    private $translator;


    public function __construct(
        DiscountRepository $discountRepository,
        SubeventRepository $subeventRepository,
        Translator $translator
    ) {
        $this->discountRepository = $discountRepository;
        $this->subeventRepository = $subeventRepository;
        $this->translator         = $translator;
    }

    /**
     * Vypočítá slevu pro kombinaci podakcí.
     * @param $selectedSubeventsIds
     */
    public function countDiscount($selectedSubeventsIds) : int
    {
        $totalDiscount = 0;

        foreach ($this->discountRepository->findAll() as $discount) {
            $this->tokenize($discount->getDiscountCondition());

            $this->selectedSubeventsIds = $selectedSubeventsIds;

            try {
                $this->parseExpression($result);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            if (! $result) {
                continue;
            }

            $totalDiscount += $discount->getDiscount();
        }

        return $totalDiscount;
    }

    /**
     * Ověří formát podmínky pro slevu.
     * @param $condition
     */
    public function validateCondition($condition) : bool
    {
        $this->tokenize($condition);

        $this->selectedSubeventsIds = [];

        try {
            $this->parseExpression($result);
        } catch (InvalidArgumentException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Převede podmínku na text.
     * @param $condition
     */
    public function convertConditionToText($condition) : string
    {
        $this->tokenize($condition);

        $text = '';

        foreach ($this->symbols as $symbol) {
            switch ($symbol['symbol']) {
                case Discount::LEFT_PARENTHESIS:
                case Discount::RIGHT_PARENTHESIS:
                    $text .= $symbol['symbol'];
                    break;

                case Discount::OPERATOR_AND:
                case Discount::OPERATOR_OR:
                    $text .= ' ' . $this->translator->translate('common.condition_operator.' . $symbol['symbol']) . ' ';
                    break;

                case Discount::SUBEVENT_ID:
                    $subevent = $this->subeventRepository->findById($symbol['value']);
                    if ($subevent === null) {
                        $text .= '"' . $this->translator->translate('admin.configuration.subevents_invalid_subevent') . '"';
                    } else {
                        $text .= '"' . $subevent->getName() . '"';
                    }
                    break;
            }
        }

        return $text;
    }

    private function tokenize($condition) : void
    {
        $tokens = explode('|', $condition);

        $this->symbols = [];
        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $this->symbols[] = ['symbol' => Discount::SUBEVENT_ID, 'value' => $token];
            } else {
                $this->symbols[] = ['symbol' => $token];
            }
        }

        $this->currentSymbol = 0;
    }

    private function nextSymbol() : void
    {
        $this->currentSymbol++;
    }

    private function symbol()
    {
        return $this->symbols[$this->currentSymbol]['symbol'];
    }

    private function symbolValue()
    {
        return $this->symbols[$this->currentSymbol]['value'];
    }

    private function accept($symbol) : void
    {
        if ($this->symbol() !== $symbol) {
            throw new InvalidArgumentException();
        }

        $this->nextSymbol();
    }

    private function acceptSubevent(&$sValue) : void
    {
        if ($this->symbol() !== Discount::SUBEVENT_ID || $this->subeventRepository->findById($this->symbolValue()) === null) {
            throw new InvalidArgumentException();
        }

        $sValue = in_array($this->symbolValue(), $this->selectedSubeventsIds) ? 1 : 0;
        $this->nextSymbol();
    }

    private function parseExpression(&$sValue) : void
    {
        switch ($this->symbol()) {
            case Discount::SUBEVENT_ID:
            case Discount::LEFT_PARENTHESIS:
                $this->parseTerm($termSValue);
                $this->parseExpression_($termSValue, $expressionSValue);
                $sValue = $expressionSValue;
                break;

            default:
                throw new InvalidArgumentException();
        }
    }

    private function parseExpression_($dValue, &$sValue) : void
    {
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
                throw new InvalidArgumentException();
        }
    }

    private function parseTerm(&$sValue) : void
    {
        switch ($this->symbol()) {
            case Discount::SUBEVENT_ID:
            case Discount::LEFT_PARENTHESIS:
                $this->parseFactor($factorSValue);
                $this->parseTerm_($factorSValue, $termSValue);
                $sValue = $termSValue;
                break;

            default:
                throw new InvalidArgumentException();
        }
    }

    private function parseTerm_($dValue, &$sValue) : void
    {
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
                throw new InvalidArgumentException();
        }
    }

    private function parseFactor(&$sValue) : void
    {
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
                throw new InvalidArgumentException();
        }
    }
}
