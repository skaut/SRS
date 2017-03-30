<?php

namespace App\Model\CMS\Content;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující obsahy webu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContentRepository extends EntityRepository
{
    /**
     * Odstraní obsah.
     * @param Content $content
     */
    public function remove(Content $content)
    {
        $this->_em->remove($content);
        $this->_em->flush();
    }
}
