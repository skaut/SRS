<?php

declare(strict_types=1);

namespace App\Model\Cms\Repositories;

use App\Model\Cms\Document;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující dokumenty.
 */
class DocumentRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Document::class);
    }

    /**
     * Vrátí dokument podle id.
     */
    public function findById(?int $id): ?Document
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací dokumenty podle rolí a vybraných tagů, seřazené podle názvu.
     *
     * @param int[] $rolesIds pole id rolí
     * @param int[] $tagsIds
     *
     * @return Collection<int, Document>
     */
    public function findRolesAllowedByTagsOrderedByName(array $rolesIds, array $tagsIds): Collection
    {
        $result = $this->createQueryBuilder('d')
            ->select('d')
            ->join('d.tags', 't')
            ->join('t.roles', 'r')
            ->where('t IN (:tagsIds)')
            ->andWhere('r IN (:rolesIds)')
            ->setParameter('tagsIds', $tagsIds)
            ->setParameter('rolesIds', $rolesIds)
            ->orderBy('d.name')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Uloží dokument.
     */
    public function save(Document $document): void
    {
        $this->em->persist($document);
        $this->em->flush();
    }

    /**
     * Odstraní dokument.
     */
    public function remove(Document $document): void
    {
        $this->em->remove($document);
        $this->em->flush();
    }
}
