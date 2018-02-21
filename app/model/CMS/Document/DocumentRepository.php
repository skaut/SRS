<?php

namespace App\Model\CMS\Document;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující dokumenty.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
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
    public function findAllByTagsOrderedByName($roles, $tags)
    {
        return $this->createQueryBuilder('d')
            ->select('d')
            ->join('d.tags', 't')
            ->join('t.roles', 'r')
            ->where('t.id IN (:ids)')
            ->andWhere('r IN (:roles)')
            ->setParameter('ids', $tags)
            ->setParameter('roles', array_keys($roles))
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
