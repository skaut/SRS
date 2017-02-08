<?php

namespace App\Model\CMS\Content;


use Kdyby\Doctrine\EntityRepository;

class ContentRepository extends EntityRepository
{
    public function findContentById($id) {
        return $this->find($id);
    }

    public function removeContent($id)
    {
        $content = $this->find($id);
        $this->_em->remove($content);
        $this->_em->flush();
    }
}