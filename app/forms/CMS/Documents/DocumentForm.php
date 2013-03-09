<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */


/**
 * Formular pro vytvoreni dokumentu
 */

namespace SRS\Form\CMS\Documents;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

class DocumentForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $tagChoices)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addText('name', 'Jméno:')
            ->addRule(Form::FILLED, 'Zadejte jméno')
            ->getControlPrototype()->class('name');


        $this->addMultiselect('tags', 'Tagy:')->setItems($tagChoices)
            ->addRule(Form::FILLED,'zadejte alespoň jeden tag');
        $this->addUpload('file', 'Soubor:');
        $this->addText('description', 'Popis:');
        $this->addSubmit('submit','Odeslat')->getControlPrototype()->class('btn');

        $this['tags']->getControlPrototype()->class('multiselect');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $docExists = $values['id'] != null;

        if (!$docExists && $values['file']->size == 0) {
            Debugger::dump('ahoj');
            $this->presenter->flashMessage('Je třeba vyplnit soubor', 'error');
        }

        else {
            if (!$docExists) {
                $document = new \SRS\Model\CMS\Documents\Document();
            }
            else {
                $document = $this->presenter->context->database->getRepository('\SRS\Model\CMS\Documents\Document')->find($values['id']);
            }

            $file = $values['file'];

            if ($file->size > 0) {
                $filePath = \SRS\Model\CMS\Documents\Document::SAVE_PATH . \Nette\Utils\Strings::random(5) . '_' . \Nette\Utils\Strings::webalize($file->getName(), '.');
                $file->move( WWW_DIR . $filePath);
                $values['file'] = $filePath;
            }
            else {
                unset($values['file']);
            }

            $document->setProperties($values, $this->presenter->context->database);

            if (!$docExists) {
                $this->presenter->context->database->persist($document);
            }
            $this->presenter->context->database->flush();
            $this->presenter->flashMessage('Dokument vytvořen', 'success');
            $this->presenter->redirect(':Back:CMS:Document:documents');
        }
    }

}