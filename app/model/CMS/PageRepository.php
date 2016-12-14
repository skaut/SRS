<?php

namespace App\Model\CMS;

use Nette;
use Kdyby;

class PageRepository extends Nette\Object
{
    private $em;
    private $pageRepository;

    public function __construct(Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->pageRepository = $em->getRepository(Page::class);
    }

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