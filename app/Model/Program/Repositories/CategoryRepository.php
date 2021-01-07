<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * Třída spravující kategorie programových bloků.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class CategoryRepository extends AbstractRepository
{
    /**
     * @return Collection<Category>
     */
    public function findAll() : Collection
    {
        $result = $this->em->getRepository(Category::class)->findAll();
        return new ArrayCollection($result);
    }

    /**
     * Vrací kategorii podle id.
     */
    public function findById(?int $id) : ?Category
    {
        return $this->em->getRepository(Category::class)->findOneBy(['id' => $id]);
    }

    /**
     * Vrací kategorie seřazené podle názvu.
     *
     * @return Category[]
     */
    public function findAllOrderedByName() : array
    {
        return $this->em->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací názvy všech kategorií.
     *
     * @return string[]
     */
    public function findAllNames() : array
    {
        $names = $this->em->getRepository(Category::class)
            ->createQueryBuilder('c')
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
    public function findOthersNames(int $id) : array
    {
        $names = $this->em->getRepository(Category::class)
            ->createQueryBuilder('c')
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
    public function getCategoriesOptions() : array
    {
        $categories = $this->em->getRepository(Category::class)
            ->createQueryBuilder('c')
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
     *
     * @throws ORMException
     */
    public function save(Category $category) : void
    {
        $this->em->persist($category);
        $this->em->flush();
    }

    /**
     * Odstraní kategorii.
     *
     * @throws ORMException
     */
    public function remove(Category $category) : void
    {
        foreach ($category->getBlocks() as $block) {
            $block->setCategory(null);
            $this->em->persist($block);
        }

        $this->em->remove($category);
        $this->em->flush();
    }
}
