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
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"content" = "Content", "textcontent" = "TextContent"})
 * @property int $order
 * @property \SRS\Model\CMS\Page $page
 */
abstract class Content extends \SRS\Model\BaseEntity
{
    public static $TYPES = array(
        'Text' => 'Text',
    );

    protected $contentType;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;


    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\CMS\Page", inversedBy="contents", cascade={"persist"})
     * @var \SRS\Model\CMS\Page
     */
    protected $page;


    public function setPosition($order)
    {
        $this->position = $order;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPage($page)
    {
        $this->page = $page;
    }

    public function getPage()
    {
        return $this->page;
    }


    /**
     * @return string
     */
    public function getFormIdentificator() {
        return "{$this->contentType}_{$this->id}";
    }

    public function addFormItems(\Nette\Application\UI\Form $form) {
        $formContainer = $form->addContainer($this->getFormIdentificator());
        $formContainer->addHidden('id')->setDefaultValue($this->id)->getControlPrototype()->class('id');
        $formContainer->addHidden('position')->getControlPrototype()->class('order');
        return $form;
    }

    public function setValuesFromPageForm(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();
        $values = $values[$this->getFormIdentificator()];
        $this->position = $values['position'];
    }


}
