<?php

namespace App\Model\CMS\Document;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující dokumenty.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentRepository extends EntityRepository
{
    /**
     * Vrátí dokument podle id.
     * @param $id
     * @return Document|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrátí dokumenty s tagem, seřazené podle názvu.
     * @param $tags
     * @return Document[]
     */
    public function findAllByTagsOrderedByName($tags)
    {
        return $this->createQueryBuilder('d')
            ->select('d')
            ->join('d.tags', 't')
            ->where('t IN (:ids)')->setParameter('ids', $tags)
            ->orderBy('d.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Uloží dokument.
     * @param Document $document
     */
    public function save(Document $document)
    {
        $this->_em->persist($document);
        $this->_em->flush();
    }

    /**
     * Odstraní dokument.
     * @param Document $document
     */
    public function remove(Document $document)
    {
        $this->_em->remove($document);
        $this->_em->flush();
    }
}
