<?php

namespace App\Model\Mailing;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující historii e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TemplateRepository extends EntityRepository
{
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
