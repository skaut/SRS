<?php

namespace App\Model\User;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita variabilní symbol.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="VariableSymbolRepository")
 * @ORM\Table(name="variable_symbol")
 */
class VariableSymbol
{
    use Identifier;

    /**
     * Variabilní symbol.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $variableSymbol;


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
    public function getVariableSymbol(): string
    {
        return $this->variableSymbol;
    }

    /**
     * @param string $variableSymbol
     */
    public function setVariableSymbol(string $variableSymbol)
    {
        $this->variableSymbol = $variableSymbol;
    }
}