<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 30.10.12
 * Time: 21:16
 * To change this template use File | Settings | File Templates.
 */
namespace BackModule;
use Nette\Application\UI\Form;

class CMSPresenter extends BasePresenter
{

    public function startup() {
        parent::startup();

    }


    public function renderDocuments() {
        $documents = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Document')->findAll();
        $this->template->documents = $documents;
    }

    public function renderDocument($docId = null) {
        if ($docId != null) {
            $doc = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Document')->find($docId);
            if ($doc == null) throw new \Nette\Application\BadRequestException('Dokument s tímto id neexistuje', 404);
            $form = $this->getComponent('documentForm');
            $form->bindEntity($doc);

            $this->template->document = $doc;
        }

        else {
            //do nothing
        }
    }

    public function handleDeleteDocument($docId) {
        $doc = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Document')->find($docId);
        if ($doc == null) throw new \Nette\Application\BadRequestException('Dokument s tímto id neexistuje', 404);
        $this->context->database->remove($doc);
        $this->context->database->flush();
        $this->flashMessage('Dokument smazán', 'success');
        $this->redirect(':Back:CMS:documents');
    }

    public function renderTags() {
        $tags = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Tag')->findAll();
        $this->template->tags = $tags;
    }

    public function renderTag($tagId = null) {
        if ($tagId != null) {
            $tag = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Tag')->find($tagId);
            if ($tag == null) throw new \Nette\Application\BadRequestException('Tag s tímto id neexistuje', 404);
            $form = $this->getComponent('tagForm');
            $form->bindEntity($tag);

            $this->template->tag = $tag;
        }
        else {
            //do nothing
        }
    }

    public function handleDeleteTag($tagId) {
        $tag = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Tag')->find($tagId);
        if ($tag == null) throw new \Nette\Application\BadRequestException('Tag s tímto id neexistuje', 404);
        $this->context->database->remove($tag);
        $this->context->database->flush();
        $this->flashMessage('Tag smazán', 'success');
        $this->redirect(':Back:CMS:Tags');
    }


    public function renderHeaderFooter() {
        $this->template->logo = $this->dbsettings->get('logo');
    }


    protected function createComponentDocumentForm($name)
    {
        $tagChoices = array();
        $tags = $this->presenter->context->database->getRepository('\SRS\Model\CMS\Documents\Tag')->findAll();
        $tagChoices = \SRS\Form\EntityForm::getFormChoices($tags);
        $form = new \SRS\Form\CMS\Documents\DocumentForm(null, null, $tagChoices);
        return $form;
    }

    protected function createComponentTagForm($name)
    {
        $roles = $this->presenter->context->database->getRepository('SRS\Model\Acl\Role')->findAll();
        $form = new \SRS\Form\CMS\Documents\TagForm(null, null, \SRS\Form\EntityForm::getFormChoices($roles));
        return $form;
    }

    protected function createComponentHeaderFooterForm() {
        $form = new \SRS\Form\CMS\HeaderFooterForm(null, null, $this->dbsettings);
        return $form;
    }


}
