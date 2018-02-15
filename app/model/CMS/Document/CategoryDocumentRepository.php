<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující kategorie dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class CategoryDocumentRepository extends EntityRepository
{
    /**
     * Vrátí kategorii podle id.
     * @param $id
     * @return CategoryDocument|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrátí kategorie podle id.
     * @param $ids
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findCategoryDocumentByIds($ids)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrátí id kategorií.
     * @param $documentCategories
     * @return array
     */
    public function findDocumentCategoriesIds($documentCategories)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $documentCategories->toArray());
    }

    /**
     * Vrátí všechny názvy kategorií.
     * @return array
     */
    public function findAllNames()
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrátí názvy kategorií, kromě kategorie s id.
     * @param $id
     * @return array
     */
    public function findOthersNames($id)
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->where('t.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Uloží kategorii.
     * @param CategoryDocument $categoryDocument
     */
    public function save(CategoryDocument $categoryDocument)
    {
        $this->_em->persist($categoryDocument);
        $this->_em->flush();
    }

    /**
     * Odstraní kategorii.
     * @param CategoryDocument $categoryDocument
     */
    public function remove(CategoryDocument $categoryDocument)
    {
        $this->_em->remove($categoryDocument);
        $this->_em->flush();
    }

    /**
     * Vrátí seznam kategorií jako možnosti pro select.
     * @return array
     */
    public function getDocumentCategoriesOptions()
    {
        $documentCategories = $this->createQueryBuilder('t')
            ->select('t.id, t.name')
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($documentCategories as $category)
            $options[$category['id']] = $category['name'];
        return $options;
    }
}
