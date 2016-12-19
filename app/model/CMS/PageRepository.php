<?php

namespace App\Model\CMS;

use  Kdyby\Doctrine\EntityRepository;

class PageRepository extends EntityRepository
{
    public function getCount()
    {
        return $this->pageRepository->countBy();
    }

    public function slugToId($slug)
    {
        try {
            $qb = $this->em->createQueryBuilder()
                ->select("p.id")
                ->from("Page", "p")
                ->where("p.slug = $slug");
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            throw new \Nette\Application\BadRequestException('Taková stránka neexistuje', 404);
        }
    }

    public function idToSlug($id)
    {
        try {
            $qb = $this->em->createQueryBuilder()
                ->select("p.id")
                ->from("Page", "p")
                ->where("p.id = $id");
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            throw new \Nette\Application\BadRequestException('Taková stránka neexistuje', 404);
        }
    }

    public function findPublishedOrderedByPosition()
    {
        $qb = $this->em->createQueryBuilder()
            ->select("p")
            ->from("Page", "p")
            ->where("p.public = 1")
            ->orderBy("p.position ASC");
        return $qb->getQuery()->getResult();
    }

}