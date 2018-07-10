<?php
declare(strict_types=1);

namespace App\Model\Mailing;

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
     * @param $id
     * @return Template|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací šablonu podle typu.
     * @param $type
     * @return Template|null
     */
    public function findByType($type)
    {
        return $this->findOneBy(['type' => $type]);
    }

    /**
     * Uloží šablonu e-mailu.
     * @param Template $template
     */
    public function save(Template $template)
    {
        $this->_em->persist($template);
        $this->_em->flush();
    }
}
