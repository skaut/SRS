<?php

declare(strict_types=1);

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * @param $id int
     */
    public function findById(int $id) : ?Document
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací dokumenty podle rolí a vybraných tagů, seřazené podle názvu.
     * @param $rolesIds int[] pole id rolí
     * @param $tags Collection|Tag[]
     * @return Collection|Document[]
     */
    public function findRolesAllowedByTagsOrderedByName(array $rolesIds, Collection $tags) : Collection
    {
        $result = $this->createQueryBuilder('d')
            ->select('d')
            ->join('d.tags', 't')
            ->join('t.roles', 'r')
            ->where('t IN (:tags)')
            ->andWhere('r IN (:rolesIds)')
            ->setParameter('tags', $tags)
            ->setParameter('rolesIds', $rolesIds)
            ->orderBy('d.name')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Uloží dokument.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Document $document) : void
    {
        $this->_em->persist($document);
        $this->_em->flush();
    }

    /**
     * Odstraní dokument.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Document $document) : void
    {
        $this->_em->remove($document);
        $this->_em->flush();
    }
}
