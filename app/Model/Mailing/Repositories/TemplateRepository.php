<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Mailing\Template;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující šablony automatických e-mailů.
 */
class TemplateRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Template::class);
    }

    /**
     * Vrací šablonu podle id.
     */
    public function findById(int|null $id): Template|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací šablonu podle typu.
     */
    public function findByType(string $type): Template|null
    {
        return $this->getRepository()->findOneBy(['type' => $type]);
    }

    /**
     * Uloží šablonu e-mailu.
     */
    public function save(Template $template): void
    {
        $this->em->persist($template);
        $this->em->flush();
    }
}
