<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:27
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 *
 * @property string $text
 */
class TextContent extends \SRS\Model\CMS\Content implements IContent
{
    protected $componentName = 'textcontent';

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $text;


    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }


    public function addFormItems(\Nette\Application\UI\Form $form) {
        $form->addTextArea("{$this->componentName}_{$this->id}_text",'Text')->setDefaultValue($this->text);
        return $form;
    }

    public function setValuesFromPageForm(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();
        $this->text = $values["{$this->componentName}_{$this->id}_text"];
    }

}
