<?php

declare(strict_types=1);

namespace App\Model\CustomInput\Repositories;

use App\Model\Acl\Role;
use App\Model\CustomInput\CustomInput;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;

use const PHP_INT_MAX;

/**
 * Třída spravující vlastní pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class CustomInputRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, CustomInput::class);
    }

    /**
     * @return Collection<CustomInput>
     */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací pole podle id.
     */
    public function findById(?int $id): ?CustomInput
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací všechna pole seřazená podle pozice.
     *
     * @return CustomInput[]
     */
    public function findAllOrderedByPosition(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.position')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací pole podle rolí uživatele, seřazené podle pozice.
     *
     * @param Collection<Role> $roles
     *
     * @return CustomInput[]
     */
    public function findByRolesOrderedByPosition(Collection $roles): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.roles', 'r')
            ->where('r IN (:roles)')->setParameter('roles', $roles)
            ->orderBy('i.position')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrátí pozici posledního pole.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastPosition(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('MAX(i.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Uloží pole.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function save(CustomInput $input): void
    {
        if (! $input->getPosition()) {
            $input->setPosition($this->findLastPosition() + 1);
        }

        $this->em->persist($input);
        $this->em->flush();
    }

    /**
     * Odstraní pole.
     *
     * @throws ORMException
     */
    public function remove(CustomInput $input): void
    {
        foreach ($input->getCustomInputValues() as $customInputValue) {
            $this->em->remove($customInputValue);
        }

        $this->em->remove($input);
        $this->em->flush();
    }

    /**
     * Přesune pole mezi pole s id prevId a nextId.
     *
     * @throws ORMException
     */
    public function sort(int $itemId, int $prevId, int $nextId): void
    {
        $item = $this->getRepository()->find($itemId);
        $prev = $prevId ? $this->getRepository()->find($prevId) : null;
        $next = $nextId ? $this->getRepository()->find($nextId) : null;

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->where('i.position <= :position')
            ->setParameter('position', $prev ? $prev->getPosition() : 0)
            ->andWhere('i.position > :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->em->persist($t);
        }

        $itemsToMoveDown = $this->createQueryBuilder('i')
            ->where('i.position >= :position')
            ->setParameter('position', $next ? $next->getPosition() : PHP_INT_MAX)
            ->andWhere('i.position < :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveDown as $t) {
            $t->setPosition($t->getPosition() + 1);
            $this->em->persist($t);
        }

        if ($prev) {
            $item->setPosition($prev->getPosition() + 1);
        } elseif ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->em->persist($item);
        $this->em->flush();
    }
}
