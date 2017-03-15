<?php

namespace App\Model\CMS\Content;

use Kdyby\Doctrine\EntityRepository;


class ContentRepository extends EntityRepository
{
    /**
     * @param Content $content
     */
    public function remove(Content $content)
    {
        $this->_em->remove($content);
        $this->_em->flush();
    }
}