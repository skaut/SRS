<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Mailing\Template;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující šablony automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
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
    public function findById(?int $id): ?Template
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací šablonu podle typu.
     */
    public function findByType(string $type): ?Template
    {
        return $this->getRepository()->findOneBy(['type' => $type]);
    }

    /**
     * Uloží šablonu e-mailu.
     *
     * @throws ORMException
     */
    public function save(Template $template): void
    {
        $this->em->persist($template);
        $this->em->flush();
    }
}
