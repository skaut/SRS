<?php
declare(strict_types=1);

namespace App\Model\Program;

use App\Model\EntityRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * Třída spravující programové bloky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlockRepository extends EntityRepository
{

	/**
	 * Vrací blok podle id.
	 */
	public function findById(?int $id): ?Block
	{
		return $this->findOneBy(['id' => $id]);
	}

	/**
	 * Vrací poslední id.
	 * @throws NonUniqueResultException
	 */
	public function findLastId(): int
	{
		return (int) $this->createQueryBuilder('b')
				->select('MAX(b.id)')
				->getQuery()
				->getSingleScalarResult();
	}

	/**
	 * Vrací názvy všech bloků.
	 * @return string[]
	 */
	public function findAllNames(): array
	{
		$names = $this->createQueryBuilder('b')
			->select('b.name')
			->getQuery()
			->getScalarResult();
		return array_map('current', $names);
	}

	/**
	 * Vrací všechny bloky seřazené podle názvu.
	 * @return Block[]
	 */
	public function findAllOrderedByName(): array
	{
		return $this->createQueryBuilder('b')
				->orderBy('b.name')
				->getQuery()
				->getResult();
	}

	/**
	 * Vrací všechny bloky nezařezené v kategorii, seřazené podle názvu.
	 * @return Block[]
	 */
	public function findAllUncategorizedOrderedByName(): array
	{
		return $this->createQueryBuilder('b')
				->where('b.category IS NULL')
				->orderBy('b.name')
				->getQuery()
				->getResult();
	}

	/**
	 * Vrací názvy ostatních bloků, kromě bloku se zadaným id.
	 * @return string[]
	 */
	public function findOthersNames(int $id): array
	{
		$names = $this->createQueryBuilder('b')
			->select('b.name')
			->where('b.id != :id')
			->setParameter('id', $id)
			->getQuery()
			->getScalarResult();
		return array_map('current', $names);
	}

	/**
	 * Vrací bloky podle textu obsaženého v názvu, seřazené podle názvu.
	 * @return Block[]
	 */
	public function findByLikeNameOrderedByName(string $text, bool $unassignedOnly = false): array
	{
		$qb = $this->createQueryBuilder('b')
				->select('b')
				->where('b.name LIKE :text')->setParameter('text', '%' . $text . '%');

		if ($unassignedOnly) {
			$qb = $qb->leftJoin('b.programs', 'p')
				->andWhere('SIZE(b.programs) = 0');
		}

		return $qb->orderBy('b.name')
				->getQuery()
				->getResult();
	}

	/**
	 * Vrací bloky, které jsou pro uživatele povinné a není na ně přihlášený.
	 * @param Collection|Category[] $categories
	 * @param Collection|Subevent[] $subevents
	 * @return Collection|Block[]
	 */
	public function findMandatoryForCategoriesAndSubevents(User $user, Collection $categories, Collection $subevents): Collection
	{
		$usersBlocks = $this->createQueryBuilder('b')
			->select('b')
			->leftJoin('b.programs', 'p')
			->leftJoin('p.attendees', 'u')
			->where('u = :user')
			->setParameter('user', $user)
			->getQuery()
			->getResult();

		$qb = $this->createQueryBuilder('b')
			->select('b')
			->leftJoin('b.category', 'c')
			->where($this->createQueryBuilder('b')->expr()->orX(
					'c IN (:categories)',
					'b.category IS NULL'
			))
			->andWhere('b.subevent IN (:usersSubevents)')
			->andWhere('b.mandatory != :voluntary')
			->setParameter('categories', $categories)
			->setParameter('usersSubevents', $subevents)
			->setParameter('voluntary', ProgramMandatoryType::VOLUNTARY);

		if (!empty($usersBlocks)) {
			$qb = $qb
				->andWhere('b NOT IN (:usersBlocks)')
				->setParameter('usersBlocks', $usersBlocks);
		}

		return new ArrayCollection($qb->getQuery()->getResult());
	}

	/**
	 * Vrací id bloků.
	 * @param Collection|Block[] $blocks
	 * @return int[]
	 */
	public function findBlocksIds(Collection $blocks): array
	{
		return array_map(function ($o) {
			return $o->getId();
		}, $blocks->toArray());
	}

	/**
	 * Vrací bloky podle id.
	 * @param int[] $ids
	 * @return Collection|Block[]
	 */
	public function findBlocksByIds(array $ids): Collection
	{
		$criteria = Criteria::create()
			->where(Criteria::expr()->in('id', $ids));
		return $this->matching($criteria);
	}

	/**
	 * Uloží blok.
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function save(Block $block): void
	{
		$this->_em->persist($block);
		$this->_em->flush();
	}

	/**
	 * Odstraní blok.
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function remove(Block $block): void
	{
		foreach ($block->getPrograms() as $program) {
			$this->_em->remove($program);
		}

		$this->_em->remove($block);
		$this->_em->flush();
	}
}
