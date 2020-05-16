<?php

declare(strict_types=1);

namespace App\Model\User\Application;

use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita variabilní symbol.
 *
 * @ORM\Entity(repositoryClass="VariableSymbolRepository")
 * @ORM\Table(name="variable_symbol")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class VariableSymbol
{
    use Id;

    /**
     * Variabilní symbol.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $variableSymbol = null;

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
