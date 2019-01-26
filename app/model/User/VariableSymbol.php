<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * Entita variabilní symbol.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="VariableSymbolRepository")
 * @ORM\Table(name="variable_symbol")
 * @ORM\Cache(usage="READ_WRITE", region="variable_symbol_region")
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


    public function getId() : int
    {
        return $this->id;
    }

    public function getVariableSymbol() : ?string
    {
        return $this->variableSymbol;
    }

    public function setVariableSymbol(?string $variableSymbol) : void
    {
        $this->variableSymbol = $variableSymbol;
    }
}
