<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;

/**
 * Třída spravující kategorie programových bloků.
 */
class CategoryRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Category::class);
    }

    /** @return Collection<int, Category> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací kategorii podle id.
     */
    public function findById(int|null $id): Category|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací kategorie seřazené podle názvu.
     *
     * @return Category[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací názvy všech kategorií.
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
     * Vrací názvy kategorií, kromě kategorie s id.
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
     * Vrací kategorie jako možnosti pro select.
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
        foreach ($categories as $category) {
            $options[$category['id']] = $category['name'];
        }

        return $options;
    }

    /**
     * Uloží kategorii.
     */
    public function save(Category $category): void
    {
        $this->em->persist($category);
        $this->em->flush();
    }

    /**
     * Odstraní kategorii.
     */
    public function remove(Category $category): void
    {
        $this->em->remove($category);
        $this->em->flush();
    }
}
