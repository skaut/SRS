<?php

namespace App\Model\CMS\Document;


use Kdyby\Doctrine\EntityRepository;

class DocumentRepository extends EntityRepository
{
    /**
     * @param $id
     * @return Document
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param Document $document
     */
    public function save(Document $document)
    {
        $this->_em->persist($document);
        $this->_em->flush();
    }

    /**
     * @param Document $document
     */
    public function remove(Document $document)
    {
        $this->_em->remove($document);
        $this->_em->flush();
    }
}