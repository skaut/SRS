<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;

/**
 * Třída spravující programy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class AbstractRepository
{
    protected EntityManagerInterface $em;

    private string $className;

    public function __construct(EntityManagerInterface $em, string $className)
    {
        $this->em        = $em;
        $this->className = $className;
    }

    public function createQueryBuilder(string $alias) : QueryBuilder
    {
        return $this->em->getRepository($this->className)->createQueryBuilder($alias);
    }

    public function getRepository() : EntityRepository
    {
        $repository = $this->em->getRepository($this->className);
        assert($repository instanceof EntityRepository);
        return $repository;
    }
}
