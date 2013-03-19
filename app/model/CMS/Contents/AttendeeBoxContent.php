<?php
/**
 * Date: 7.1.13
 * Time: 20:38
 * Author: Michal Májský
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AttendeeBoxContent extends \SRS\Model\CMS\Content implements IContent
{
    protected $contentType = 'attendeeboxcontent';
    protected $contentName = 'Přihlašovací formulář';


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

