<?php

declare(strict_types=1);

namespace App\Model\Structure\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Structure\Discount;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující slevy.
 */
class DiscountRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Discount::class);
    }

    /**
     * @return Collection<int, Discount>
     */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací slevu podle id.
     */
    public function findById(?int $id): ?Discount
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Uloží slevu.
     *
     */
    public function save(Discount $discount): void
    {
        $this->em->persist($discount);
        $this->em->flush();
    }

    /**
     * Odstraní slevu.
     *
     */
    public function remove(Discount $discount): void
    {
        $this->em->remove($discount);
        $this->em->flush();
    }
}
