<?php

namespace App\Model;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\QueryException;

/**
 * Custom base EntityRepository
 */
abstract class EntityRepository extends DoctrineEntityRepository
{

	/**
	 * Fetches all records like $key => $value pairs
	 *
	 * @param array $criteria parameter can be skipped
	 * @param string $value mandatory
	 * @param string $key optional
	 *
	 * @throws QueryException
	 * @return array
	 */
	public function findPairs($criteria, $value = null, $key = null)
	{
		if (!$key) {
			$key = $this->getClassMetadata()->getSingleIdentifierFieldName();
		}

		/** @var QueryBuilder $qb */
		$qb = $this->createQueryBuilder('e');

		foreach ($criteria as $criteriaKey => $criteriaValue) {
			$qb->andWhere('e.' . $criteriaKey . ' = :value')
				->setParameter('value', $criteriaValue);
		}

		$qb->select(["e.$value", "e.$key"])
			->resetDQLPart('from')->from($this->getEntityName(), 'e', 'e.' . $key);

		$query = $qb->getQuery();

		try {
			return array_map(function ($row) {
				return reset($row);
			}, $query->getResult(AbstractQuery::HYDRATE_ARRAY));
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}
}
