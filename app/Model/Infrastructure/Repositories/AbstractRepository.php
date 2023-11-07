<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use function assert;

/**
 * Třída spravující programy.
 */
class AbstractRepository
{
    /** @param class-string $className */
    public function __construct(protected EntityManagerInterface $em, private readonly string $className)
    {
    }

    public function createQueryBuilder(string $alias): QueryBuilder
    {
        return $this->getRepository()->createQueryBuilder($alias);
    }

    /** @return EntityRepository<object> */
    public function getRepository(): EntityRepository
    {
        $repository = $this->em->getRepository($this->className);
        assert($repository instanceof EntityRepository);

        return $repository;
    }
}
