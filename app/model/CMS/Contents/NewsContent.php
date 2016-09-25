<?php
/**
 * Date: 15.11.12
 * Time: 13:27
 * Author: Michal Májský
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 *
 * @property integer $count
 */
class NewsContent extends \SRS\Model\CMS\Content implements IContent
{
    protected $contentType = 'newscontent';
    protected $contentName = 'Aktuality';

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var integer
     */
    protected $count;

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function getCount()
    {
        return $this->count;
    }


    public function addFormItems(\Nette\Application\UI\Form $form)
    {
        parent::addFormItems($form);
        $formContainer = $form[$this->getFormIdentificator()];
        $formContainer->addText("count", 'Počet zobrazovaných aktualit:')
            ->setDefaultValue($this->count)
            ->getControlPrototype()->class('number')
            ->addCondition(\Nette\Application\UI\Form::FILLED)
            ->addRule(\Nette\Application\UI\Form::INTEGER, 'Musí být číslo');

        return $form;
    }

    public function setValuesFromPageForm(\Nette\Application\UI\Form $form)
    {
        parent::setValuesFromPageForm($form);
        $values = $form->getValues();
        $values = $values[$this->getFormIdentificator()];
        $this->count = (int) $values['count'];
    }

    public function getContentName()
    {
        return $this->contentName;
    }


}
