<?php

declare(strict_types=1);

namespace App\Model;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use function array_map;
use function reset;

/**
 * Custom base EntityRepository
 */
abstract class EntityRepository extends DoctrineEntityRepository
{
    /**
     * Fetches all records like $key => $value pairs
     *
     * @param string[] $criteria parameter can be skipped
     * @param string   $value    mandatory
     * @param string   $key      optional
     *
     * @throws QueryException
     * @return string[]
     */
    public function findPairs($criteria = [], ?string $value = null, ?string $key = null) : array
    {
        if (! $key) {
            $key = $this->getClassMetadata()->getSingleIdentifierFieldName();
        }

        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->createQueryBuilder('e');

        foreach ($criteria as $criteriaKey => $criteriaValue) {
            $qb->andWhere('e.' . $criteriaKey . ' = :value')
                    ->setParameter('value', $criteriaValue);
        }

        $qb->select(['e.' . $value, 'e.' . $key])
                ->resetDQLPart('from')->from($this->getEntityName(), 'e', 'e.' . $key);

        $query = $qb->getQuery();

        try {
            return array_map(
                function ($row) {
                        return reset($row);
                },
                $query->getResult(AbstractQuery::HYDRATE_ARRAY)
            );
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
