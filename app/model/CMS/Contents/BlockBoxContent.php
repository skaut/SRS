<?php
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class BlockBoxContent extends \SRS\Model\CMS\Content implements IContent
{
    protected $contentType = 'blockboxcontent';
    protected $contentName = 'Program semináře';


    public function addFormItems(\Nette\Application\UI\Form $form)
    {
        parent::addFormItems($form);
        return $form;
    }

    public function setValuesFromPageForm(\Nette\Application\UI\Form $form)
    {
        parent::setValuesFromPageForm($form);
    }

    public function getContentName()
    {
        return $this->contentName;
    }
}

