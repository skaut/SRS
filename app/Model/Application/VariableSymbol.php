<?php

declare(strict_types=1);

namespace App\Model\Application;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita variabilní symbol.
 */
#[ORM\Entity]
#[ORM\Table(name: 'variable_symbol')]
class VariableSymbol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Variabilní symbol.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $variableSymbol = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getVariableSymbol(): string|null
    {
        return $this->variableSymbol;
    }

    public function setVariableSymbol(string|null $variableSymbol): void
    {
        $this->variableSymbol = $variableSymbol;
    }
}
