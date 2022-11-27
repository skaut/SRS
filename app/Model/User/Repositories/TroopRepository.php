<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Enums\TroopApplicationState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\User\Troop;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

use function array_map;

/**
 * Třída spravující oddíly.
 */
class TroopRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Troop::class);
    }

    public function findById(int $id): Troop
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * @return Collection<int, Troop>
     */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByLeaderId(int $leaderId): ?Troop
    {
        return $this->getRepository()
            ->createQueryBuilder('t')
            ->where('t.leader = :leader_id')
            ->setParameter('leader_id', $leaderId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByVariableSymbol(?string $variableSymbol): ?Troop
    {
        $variableSymbolRegex = '^0*' . $variableSymbol . '$';

        return $this->createQueryBuilder('t')
            ->select('t')
            ->join('t.variableSymbol', 'v')
            ->where('REGEXP(v.variableSymbol, :variableSymbol) = 1')->setParameter('variableSymbol', $variableSymbolRegex)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vrací skupiny podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, Troop>
     */
    public function findTroopsByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací id skupin.
     *
     * @param Collection<int, Troop> $troops
     *
     * @return int[]
     */
    public function findTroopsIds(Collection $troops): array
    {
        return array_map(static fn (Troop $t) => $t->getId(), $troops->toArray());
    }

    /**
     * @param Collection<int, Troop> $pairedTroops
     *
     * @return Collection<int, Troop>
     */
    public function findWaitingForPaymentOrPairedTroops(Collection $pairedTroops): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->orX(
                Criteria::expr()->eq('state', TroopApplicationState::WAITING_FOR_PAYMENT),
                Criteria::expr()->in('id', $pairedTroops->map(static fn (Troop $troop) => $troop->getId())
                    ->toArray())
            ));

        return $this->getRepository()->matching($criteria);
    }

    /**
     * @param Collection<int, Troop> $pairedTroops
     *
     * @return string[]
     */
    public function getWaitingForPaymentOrPairedTroopsVariableSymbolsOptions(Collection $pairedTroops): array
    {
        $options = [];
        foreach ($this->findWaitingForPaymentOrPairedTroops($pairedTroops) as $troop) {
            $options[$troop->getId()] = $troop->getName() . ' (' . $troop->getVariableSymbolText() . ' - ' . $troop->getFee() . ' Kč)';
        }

        return $options;
    }

    public function save(Troop $troop): void
    {
        $this->em->persist($troop);
        $this->em->flush();
    }
}
