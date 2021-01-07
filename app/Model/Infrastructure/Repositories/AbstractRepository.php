<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Nettrine\ORM\EntityManagerDecorator;

/**
 * Třída spravující programy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class AbstractRepository
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
}
