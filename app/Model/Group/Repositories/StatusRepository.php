<?php

declare(strict_types=1);

namespace App\Model\Group\Repositories;

use App\Model\Group\Status;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;

/**
 * Třída spravující statusy programových bloků.
 */
class StatusRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Status::class);
    }

    /** @return Collection<int, Status> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací status podle id.
     */
    public function findById(int|null $id): Status|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací statusy seřazené podle názvu.
     *
     * @return Status[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací názvy všech statusů.
     *
     * @return string[]
     */
    public function findAllNames(): array
    {
        $names = $this->createQueryBuilder('c')
            ->select('c.name')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací názvy statusů, kromě statusů s id.
     *
     * @return string[]
     */
    public function findOthersNames(int $id): array
    {
        $names = $this->createQueryBuilder('c')
            ->select('c.name')
            ->where('c.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací statusy jako možnosti pro select.
     *
     * @return string[]
     */
    public function getCategoriesOptions(): array
    {
        $categories = $this->createQueryBuilder('c')
            ->select('c.id, c.name')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($statuses as $status) {
            $options[$status['id']] = $status['name'];
        }

        return $options;
    }

    /**
     * Uloží status.
     */
    public function save(Status $status): void
    {
        $this->em->persist($status);
        $this->em->flush();
    }

    /**
     * Odstraní status.
     */
    public function remove(Status $status): void
    {
        $this->em->remove($status);
        $this->em->flush();
    }
}
