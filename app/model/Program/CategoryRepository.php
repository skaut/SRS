<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class CategoryRepository extends EntityRepository
{
    public function addCategory($name, $roles) {
        $category = new Category();

        $category->setName($name);
        $category->setRegisterableRoles($roles);

        $this->_em->persist($category);
        $this->_em->flush();
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
    }

    public function isNameUnique($name, $id = null) {
        $tag = $this->findOneBy(['name' => $name]);
        if ($tag) {
            if ($id == $tag->getId())
                return true;
            return false;
        }
        return true;
    }

    public function findCategoryById($id) {
        return $this->find($id);
    }

    public function findCategoriesOrderedByName() {
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }
}