<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Structure\Discount;
use App\Model\Structure\Repositories\DiscountRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use InvalidArgumentException;
use Nette;
use Nette\Localization\Translator;

use function explode;
use function in_array;
use function is_numeric;

/**
 * Služba pro správu slev.
 */
class DiscountService
{
    use Nette\SmartObject;

    /**
     * Tokeny podmínky.
     *
     * @var string[][]
     */
    private array $symbols;

    /**
     * Aktuálně zpracovávaný token.
     */
    private int $currentSymbol;

    /**
     * Id zvolených podakcí.
     *
     * @var int[]
     */
    private array $selectedSubeventsIds;

    public function __construct(
        private DiscountRepository $discountRepository,
        private SubeventRepository $subeventRepository,
        private Translator $translator
    ) {
    }

    /**
     * Vypočítá slevu pro kombinaci podakcí.
     *
     * @param int[] $selectedSubeventsIds
     */
    public function countDiscount(array $selectedSubeventsIds): int
    {
        $totalDiscount = 0;

        foreach ($this->discountRepository->findAll() as $discount) {
            $this->tokenize($discount->getDiscountCondition());

            $this->selectedSubeventsIds = $selectedSubeventsIds;

            try {
                $this->parseExpression($result);
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($result) {
                $totalDiscount += $discount->getDiscount();
            }
        }

        return $totalDiscount;
    }

    /**
     * Ověří formát podmínky pro slevu.
     */
    public function validateCondition(string $condition): bool
    {
        $this->tokenize($condition);

        $this->selectedSubeventsIds = [];

        try {
            $this->parseExpression($result);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * Převede podmínku na text.
     */
    public function convertConditionToText(string $condition): string
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
                    $subevent = $this->subeventRepository->findById((int) $symbol['value']);
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

    private function tokenize(string $condition): void
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

    private function nextSymbol(): void
    {
        $this->currentSymbol++;
    }

    private function symbol(): string
    {
        return $this->symbols[$this->currentSymbol]['symbol'];
    }

    private function symbolValue(): string
    {
        return $this->symbols[$this->currentSymbol]['value'];
    }

    private function accept(string $symbol): void
    {
        if ($this->symbol() !== $symbol) {
            throw new InvalidArgumentException();
        }

        $this->nextSymbol();
    }

    private function acceptSubevent(?int &$sValue): void
    {
        if ($this->symbol() !== Discount::SUBEVENT_ID || $this->subeventRepository->findById((int) $this->symbolValue()) === null) {
            throw new InvalidArgumentException();
        }

        $sValue = in_array($this->symbolValue(), $this->selectedSubeventsIds) ? 1 : 0;
        $this->nextSymbol();
    }

    private function parseExpression(?int &$sValue): void
    {
        switch ($this->symbol()) {
            case Discount::SUBEVENT_ID:
            case Discount::LEFT_PARENTHESIS:
                $this->parseTerm($termSValue);
                $this->parseExpressionRest($termSValue, $expressionSValue);
                $sValue = $expressionSValue;
                break;

            default:
                throw new InvalidArgumentException();
        }
    }

    private function parseExpressionRest(int $dValue, ?int &$sValue): void
    {
        switch ($this->symbol()) {
            case Discount::OPERATOR_OR:
                $this->accept(Discount::OPERATOR_OR);
                $this->parseTerm($termSValue);
                $this->parseExpressionRest($dValue | $termSValue, $expressionSValue);
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

    private function parseTerm(?int &$sValue): void
    {
        switch ($this->symbol()) {
            case Discount::SUBEVENT_ID:
            case Discount::LEFT_PARENTHESIS:
                $this->parseFactor($factorSValue);
                $this->parseTermRest($factorSValue, $termSValue);
                $sValue = $termSValue;
                break;

            default:
                throw new InvalidArgumentException();
        }
    }

    private function parseTermRest(int $dValue, ?int &$sValue): void
    {
        switch ($this->symbol()) {
            case Discount::OPERATOR_AND:
                $this->accept(Discount::OPERATOR_AND);
                $this->parseFactor($factorSValue);
                $this->parseTermRest($dValue & $factorSValue, $termSValue);
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

    private function parseFactor(?int &$sValue): void
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
