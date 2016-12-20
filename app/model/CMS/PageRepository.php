<?php

namespace App\Model\CMS;

use  Kdyby\Doctrine\EntityRepository;

class PageRepository extends EntityRepository
{
    public function slugToId($slug)
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function idToSlug($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findPublishedPagesOrderedByPosition()
    {
        return $this->findBy(['public' => true], ['position' => 'ASC']);
    }

    public function findPublishedPageBySlug($slug)
    {
        return $this->findOneBy(['public' => true, 'slug' => $slug]);
    }
}