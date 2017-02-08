<?php

namespace App\Model\CMS;

use  Kdyby\Doctrine\EntityRepository;

class PageRepository extends EntityRepository
{
    public function slugToId($slug)
    {
        return $this->findOneBy(['slug' => $slug])->getId();
    }

    public function idToSlug($id)
    {
        return $this->findOneBy(['id' => $id])->getSlug();
    }

    public function findPageBySlug($slug)
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findPublishedPagesOrderedBySlug()
    {
        return $this->findBy(['public' => true], ['slug' => 'ASC']);
    }

    public function findPublishedPagesOrderedByPosition()
    {
        return $this->findBy(['public' => true], ['position' => 'ASC']);
    }

    public function findPublishedPageBySlug($slug)
    {
        return $this->findOneBy(['public' => true, 'slug' => $slug]);
    }

    public function findPageById($id) {
        return $this->find($id);
    }

    public function findAllSlugs() {
        $slugs = $this->createQueryBuilder('p')
            ->select('p.slug')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $slugs);
    }

    public function findOthersSlugs($id) {
        $slugs = $this->createQueryBuilder('p')
            ->select('p.slug')
            ->where('p.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $slugs);
    }

    public function addPage($name, $slug, $roles, $public) {
        $page = new Page($name, $slug);

        $page->setPosition($this->countBy() + 1);
        $page->setRoles($roles);
        $page->setPublic($public);

        $this->_em->persist($page);
        $this->_em->flush();

        return $page;
    }

    public function editPage($id, $name, $slug, $roles, $public) {
        $page = $this->find($id);

        $page->setName($name);
        $page->setSlug($slug);
        $page->setRoles($roles);
        $page->setPublic($public);

        $this->_em->flush();

        return $page;
    }

    public function setPagePublic($id, $public) {
        $page = $this->find($id);
        $page->setPublic($public);
        $this->_em->flush();
        return $page;
    }

    public function removePage($id)
    {
        $page = $this->find($id);

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->andWhere('i.position > :position')
            ->setParameter('position', $page->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->_em->persist($t);
        }

        $this->_em->remove($page);
        $this->_em->flush();
    }

    public function changePosition($itemId, $prevId, $nextId) {
        $item = $this->find($itemId);
        $prev = $prevId ? $this->find($prevId) : null;
        $next = $nextId ? $this->find($nextId) : null;

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->where('i.position <= :position')
            ->setParameter('position', $prev ? $prev->getPosition() : 0)
            ->andWhere('i.position > :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->_em->persist($t);
        }

        $itemsToMoveDown = $this->createQueryBuilder('i')
            ->where('i.position >= :position')
            ->setParameter('position', $next ? $next->getPosition() : PHP_INT_MAX)
            ->andWhere('i.position < :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveDown as $t) {
            $t->setPosition($t->getPosition() + 1);
            $this->_em->persist($t);
        }

        if ($prev) {
            $item->setPosition($prev->getPosition() + 1);
        } else if ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->_em->persist($item);
        $this->_em->flush();
    }
}