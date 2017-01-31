<?php

namespace App\Model\CMS\Document;


use Kdyby\Doctrine\EntityRepository;

class TagRepository extends EntityRepository
{
    public function addTag($name) {
        $tag = new Tag();
        $tag->setName($name);
        $this->_em->persist($tag);
        $this->_em->flush();
    }

    public function removeTag($id)
    {
        $tag = $this->find($id);
        $this->_em->remove($tag);
        $this->_em->flush();
    }

    public function editTag($id, $name) {
        $tag = $this->find($id);
        $tag->setName($name);
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
}