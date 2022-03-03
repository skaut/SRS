<?php

declare(strict_types=1);

namespace App\Model\Cms\Repositories;

use App\Model\Cms\Content;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující obsahy webu.
 */
class ContentRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Content::class);
    }

    /**
     * Uloží obsah.
     */
    public function save(Content $content): void
    {
        $this->em->persist($content);
        $this->em->flush();
    }

    /**
     * Odstraní obsah.
     */
    public function remove(Content $content): void
    {
        $this->em->remove($content);
        $this->em->flush();
    }
}
