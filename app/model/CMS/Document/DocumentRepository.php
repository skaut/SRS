<?php

namespace App\Model\CMS\Document;


use Kdyby\Doctrine\EntityRepository;

class DocumentRepository extends EntityRepository
{
    public function addDocument($name, $tags, $file, $description) {
        $document = new Document();

        $document->setName($name);
        $document->setTags($tags);
        $document->setFile($file);
        $document->setDescription($description);
        $document->setTimestamp(new \DateTime());

        $this->_em->persist($document);
        $this->_em->flush();

        return $document;
    }

    public function removeDocument($id)
    {
        $document = $this->find($id);
        $this->_em->remove($document);
        $this->_em->flush();
    }

    public function editDocument($id, $name, $tags, $file, $description) {
        $document = $this->find($id);

        $document->setName($name);
        $document->setTags($tags);
        if ($file)
            $document->setFile($file);
        $document->setDescription($description);
        $document->setTimestamp(new \DateTime());

        $this->_em->flush();

        return $document;
    }
}