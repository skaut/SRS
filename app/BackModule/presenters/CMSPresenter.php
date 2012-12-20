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
    protected function createComponentUserGrid()
    {
        return new \SRS\Components\UserGrid($this->context->database);
    }

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


    }


    protected function createComponentNewPageForm($name)
    {
        $form = new \SRS\Form\CMS\NewPageForm();
        return $form;
    }


    protected function createComponentPageForm($name) {
        $pageId = $this->getParameter('pageId');
        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
        if ($page == null) throw new \Nette\Application\BadRequestException('Stránka s tímto id neexistuje');

        $form = new \SRS\Form\EntityForm();
        $form->addHidden('id');
        $form->addText('name', 'Jméno stránky:')->getControlPrototype()->class('name')
            ->addRule(Form::FILLED, 'Zadejte jméno');
        $form->addText('slug','Slug:')->getControlPrototype()->class('slug')
            ->addRule(Form::FILLED, 'Zadejte slug');
        $form->addSelect('add_content', 'Přidat obsah', \SRS\Model\CMS\Content::$TYPES)->setPrompt('vyber typ');

        $form->bindEntity($page);

        foreach ($page->contents as $content) {
            $form = $content->addFormItems($form);
        }

        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = callback($this, 'pageFormSubmitted');

        return $form;
    }

    public function pageFormSubmitted(\SRS\Form\EntityForm $form) {
        $values = $form->getValues();
        $pageId = $values['id'];

        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
        $page->setProperties($values);

        foreach ($page->contents as $content) {
            $content->setValuesFromPageForm($form);
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
        $this->redirect('this');

    }


}
