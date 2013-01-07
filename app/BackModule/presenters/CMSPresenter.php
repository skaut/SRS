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

    public function renderPages() {
        //$pages = $this->context->database->getRepository('\SRS\Model\CMS\Page')->findAll();
        $query = $this->context->database->createQuery('SELECT p FROM \SRS\Model\CMS\Page p ORDER BY p.position');
        $pages = $query->getResult();
        $this->template->pages = $pages;
    }

    public function handleSortPages() {
        $pagesOrder = $this->getParameter('pages');
        $position = 0;
        foreach ($pagesOrder as $pageId) {
            $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
            $page->position = $position;
            $this->context->database->persist($page);
            $position++;
        }
        $this->context->database->flush();
        $this->flashMessage('Pořadí stránek uloženo', 'success');
        $this->invalidateControl('pagelist');
        $this->invalidateControl('flashMessages');


    }

    public function renderPage($pageId) {
        if ($pageId == null) {
            $this->flashMessage('Nebyla zvolena stránka', 'error');
            $this->redirect(':Back:CMS:Pages');
        }
        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
        if ($page == null) throw new \Nette\Application\BadRequestException('Stránka s tímto id neexistuje');

        $this->template->page = $page;

    }

    public function handleDeletePage($pageId) {
        if ($pageId == null) {
            $this->flashMessage('Nebyla zvolena stránka', 'error');
            $this->redirect(':Back:CMS:Pages');
        }
        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
        if ($page == null) throw new \Nette\Application\BadRequestException('Stránka s tímto id neexistuje');
        foreach ($page->contents as $content) {
            $this->context->database->remove($content);
        }
        $this->context->database->remove($page);
        $this->context->database->flush();
        $this->flashMessage('Stránka smazána', 'success');
        $this->redirect(':Back:CMS:Pages');

    }


    public function renderDocuments() {
        $documents = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Document')->findAll();
        $this->template->documents = $documents;
    }

    public function renderDocument($docId = null) {
        if ($docId != null) {
            $doc = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Document')->find($docId);
            if ($doc == null) throw new \Nette\Application\BadRequestException('Dokument s tímto id neexistuje');
            $form = $this->getComponent('documentForm');
            $form->bindEntity($doc);
        }
    }

    public function handleDeleteDocument($docId) {
        $doc = $this->context->database->getRepository('\SRS\Model\CMS\Documents\Document')->find($docId);
        if ($doc == null) throw new \Nette\Application\BadRequestException('Dokument s tímto id neexistuje');
        $this->context->database->remove($doc);
        $this->context->database->flush();
        $this->flashMessage('Dokument smazán', 'success');
        $this->redirect(':Back:CMS:documents');

    }


    protected function createComponentNewPageForm($name)
    {
        $form = new \SRS\Form\CMS\NewPageForm();
        return $form;
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


    protected function createComponentPageForm($name) {
        $pageId = $this->getParameter('pageId');
        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
        $roles = $this->context->database->getRepository('\SRS\Model\Acl\Role')->findAll();
        $roleChoices = \SRS\Form\EntityForm::getFormChoices($roles);
        if ($page == null) throw new \Nette\Application\BadRequestException('Stránka s tímto id neexistuje');
        $form = new \SRS\Form\CMS\PageForm(null, null, $roleChoices);
        $form->bindEntity($page);

        foreach ($page->contents as $content) {
            $content->setEntityManager($this->context->database);
            $form = $content->addFormItems($form); // pridavame polozky formulare, ktere souvisi s jednotlivymi contenty
            $contentFormContainer = $form[$content->getFormIdentificator()];
            $contentFormContainer->addHidden('delete', 'smazat')->setDefaultValue(0);

        }


        $form->onSuccess[] = callback($this, 'pageFormSubmitted');

        return $form;
    }

    public function pageFormSubmitted(\SRS\Form\EntityForm $form) {
        $values = $form->getValues();
        $pageId = $values['id'];

        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);

        $page->setProperties($values, $this->context->database);

        foreach ($page->contents as $content) {

            $contentFormContainer = $values[$content->getFormIdentificator()];
            $deleteContent = $contentFormContainer['delete'];
            if ($deleteContent == true) $this->context->database->remove($content);
            else $content->setValuesFromPageForm($form);

        }
        if ($values['add_content'] != null) {
            $contentTypeStr = '\\SRS\\Model\\CMS\\'.$values['add_content'].'Content';
            $contentType = new $contentTypeStr();
            $contentType->page = $page;
            $this->context->database->persist($contentType);
            $page->contents->add($contentType); // TODO nefunguje fix
            $this->flashMessage("Obsah typu {$values['add_content']} přidán");
        }
        $this->context->database->flush();
        $this->flashMessage('Stránka uložena');

        $submitName = ($form->isSubmitted());
        $submitName = $submitName->htmlName;

        if ($submitName == 'submit_to_list') $this->redirect(':Back:CMS:pages');
        $this->redirect('this');

    }


}
