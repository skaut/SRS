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
 *
 * @property \SRS\Model\CMS\Documents\Tag $tag
 */
class DocumentContent extends \SRS\Model\CMS\Content implements IContent
{
    protected $contentType = 'documentcontent';
    protected $contentName = 'Dokumenty';

    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\CMS\Documents\Tag", cascade={"persist"})
     * @var \SRS\Model\CMS\Documents\Tag
     */
    protected $tag;


    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function getTag()
    {
        return $this->tag;
    }


    public function addFormItems(\Nette\Application\UI\Form $form)
    {
        parent::addFormItems($form);

        $formContainer = $form[$this->getFormIdentificator()];
        $tags = $this->em->getRepository('\SRS\Model\CMS\Documents\Tag')->findAll();
        $tagChoices = \SRS\Form\EntityForm::getFormChoices($tags);
        $defaultValue = $this->tag ? $this->tag->id : null;
        $formContainer->addSelect("tag", 'Tag')->setPrompt('Všechny dokumenty')->setItems($tagChoices)->setDefaultValue($defaultValue);
        return $form;
    }

    public function setValuesFromPageForm(\Nette\Application\UI\Form $form)
    {
        parent::setValuesFromPageForm($form);
        $values = $form->getValues();
        $values = $values[$this->getFormIdentificator()];
        if ($values['tag'] == null) {
            $this->tag = null;
        } else {
            $this->tag = $this->em->getReference('\SRS\Model\CMS\Documents\Tag', $values['tag']);
        }
        //$this->setProperties($values, $this->em);
    }

    public function getContentName()
    {
        return $this->contentName;
    }
}

