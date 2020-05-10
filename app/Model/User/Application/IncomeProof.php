<?php

declare(strict_types=1);

namespace App\Model\User\Application;

use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita příjmového dokladu.
 *
 * @ORM\Entity(repositoryClass="IncomeProof")
 * @ORM\Table(name="income_proof")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class IncomeProof
{
    use Id;

    public function getId() : int
    {
        return $this->id;
    }
}
