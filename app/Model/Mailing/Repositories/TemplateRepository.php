<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Mailing\Template;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující šablony automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
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
     *
     * @throws ORMException
     */
    public function save(Template $template) : void
    {
        $this->_em->persist($template);
        $this->_em->flush();
    }
}
