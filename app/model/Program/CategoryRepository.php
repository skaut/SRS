<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class CategoryRepository extends EntityRepository
{
    public function findAllNames() {
        $names = $this->createQueryBuilder('c')
            ->select('c.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('c')
            ->select('c.name')
            ->where('c.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function addCategory($name, $roles) {
        $category = new Category();

        $category->setName($name);
        $category->setRegisterableRoles($roles);

        $this->_em->persist($category);
        $this->_em->flush();

        return $category;
    }

    public function removeCategory($id)
    {
        $category = $this->find($id);
        $this->_em->remove($category);
        $this->_em->flush();
    }

    public function editCategory($id, $name, $roles) {
        $category = $this->find($id);

        $category->setName($name);
        $category->setRegisterableRoles($roles);

        $this->_em->flush();

        return $category;
    }

    public function findCategoryById($id) {
        return $this->find($id);
    }

    public function findCategoriesOrderedByName() {
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

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
}