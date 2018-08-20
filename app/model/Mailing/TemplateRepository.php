<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;

/**
 * Třída spravující šablony automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TemplateRepository extends EntityRepository
{
    /**
     * Vrací šablonu podle id.
     */
    public function findById(?int $id) : ?Template
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací šablonu podle typu.
     */
    public function findByType(string $type) : ?Template
    {
        return $this->findOneBy(['type' => $type]);
    }

    /**
     * Uloží šablonu e-mailu.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Template $template) : void
    {
        $this->_em->persist($template);
        $this->_em->flush();
    }
}
