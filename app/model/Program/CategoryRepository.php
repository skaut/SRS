<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class CategoryRepository extends EntityRepository
{
    /**
     * @param $id
     * @return Category|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findAllOrderedByName() {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findAllNames() {
        $names = $this->createQueryBuilder('c')
            ->select('c.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param $id
     * @return array
     */
    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('c')
            ->select('c.name')
            ->where('c.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @return array
     */
    public function getCategoriesOptions() {
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
     * @param Category $category
     */
    public function save(Category $category)
    {
        $this->_em->persist($category);
        $this->_em->flush();
    }

    /**
     * @param Category $category
     */
    public function remove(Category $category)
    {
        $this->_em->remove($category);
        $this->_em->flush();
    }
}