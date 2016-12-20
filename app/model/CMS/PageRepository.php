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
            $qb = $this->_em->createQueryBuilder()
                ->select("p.id")
                ->from(Page::class, "p")
                ->where("p.slug = $slug");
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $ex) {
            throw new \Nette\Application\BadRequestException('Page not found', 404);
        }
    }

    public function idToSlug($id)
    {
        try {
            $qb = $this->_em->createQueryBuilder()
                ->select("p.id")
                ->from(Page::class, "p")
                ->where("p.id = $id");
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            throw new \Nette\Application\BadRequestException('Page not found', 404);
        }
    }

    public function findPublishedPagesOrderedByPosition()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select("p")
            ->from(Page::class, "p")
            ->where("p.public = 1")
            ->orderBy("p.position", "ASC");
        return $qb->getQuery()->getResult();
    }

}