<?php

declare(strict_types=1);

namespace App\Model\Application;

use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita příjmového dokladu.
 *
 * @ORM\Entity
 * @ORM\Table(name="income_proof")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class IncomeProof
{
    use Id;

    /**
     * Datum vystavení příjmového dokladu.
     *
     * @ORM\Column(type="date_immutable")
     */
    protected DateTimeImmutable $date;

    public function __construct()
    {
        $this->date = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Vrací datum vytištění dokladu jako text.
     */
    public function getDateText(): string
    {
        return $this->date->format(Helpers::DATE_FORMAT);
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }
}
