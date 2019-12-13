<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use function array_map;

/**
 * Třída spravující kategorie programových bloků.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class CategoryRepository extends EntityRepository
{
    /**
     * Vrací kategorii podle id.
     */
    public function findById(?int $id) : ?Category
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací kategorie seřazené podle názvu.
     * @return Category[]
     */
    public function findAllOrderedByName() : array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací názvy všech kategorií.
     * @return string[]
     */
    public function findAllNames() : array
    {
        $names = $this->createQueryBuilder('c')
            ->select('c.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrací názvy kategorií, kromě kategorie s id.
     * @return string[]
     */
    public function findOthersNames(int $id) : array
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
     * Vrací kategorie, ze kterých si uživatel může vybírat programy.
     * @return Collection|Category[]
     */
    public function findUserAllowed(User $user) : Collection
    {
        $result = $this->createQueryBuilder('c')
            ->join('c.registerableRoles', 'r')
            ->join('r.users', 'u')
            ->where('u = :user')->setParameter('user', $user)
            ->getQuery()
            ->getResult();
        return new ArrayCollection($result);
    }

    /**
     * Vrací kategorie jako možnosti pro select.
     * @return string[]
     */
    public function getCategoriesOptions() : array
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
    public function save(Category $category) : void
    {
        $this->_em->persist($category);
        $this->_em->flush();
    }

    /**
     * Odstraní kategorii.
     */
    public function remove(Category $category) : void
    {
        foreach ($category->getBlocks() as $block) {
            $block->setCategory(null);
            $this->_em->persist($block);
        }

        $this->_em->remove($category);
        $this->_em->flush();
    }
}
