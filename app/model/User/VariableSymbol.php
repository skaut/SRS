<?php
declare(strict_types=1);

namespace App\Model\User;

use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;

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

	public function getId(): int
	{
		return $this->id;
	}

	public function getVariableSymbol(): ?string
	{
		return $this->variableSymbol;
	}

	public function setVariableSymbol(?string $variableSymbol): void
	{
		$this->variableSymbol = $variableSymbol;
	}
}
