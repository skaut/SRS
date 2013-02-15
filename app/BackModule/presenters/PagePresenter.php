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

class PagePresenter extends BasePresenter
{
    protected $resource = "CMS";

    public function startup() {
        parent::startup();

        $this->checkPermissions('Spravovat');

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

    public function renderPage($id = null, $area = null) {
        if ($id == null) {
            $this->flashMessage('Nebyla zvolena stránka', 'error');
            $this->redirect(':Back:Page:Pages');
        }
        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($id);
        if ($page == null) throw new \Nette\Application\BadRequestException('Stránka s tímto id neexistuje');

        $this->template->page = $page;

    }

    public function handleDeletePage($pageId) {
        if ($pageId == null) {
            $this->flashMessage('Nebyla zvolena stránka', 'error');
            $this->redirect(':Back:Page:Pages');
        }
        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
        if ($page == null) throw new \Nette\Application\BadRequestException('Stránka s tímto id neexistuje');
        foreach ($page->contents as $content) {
            $this->context->database->remove($content);
        }
        $this->context->database->remove($page);
        $this->context->database->flush();
        $this->flashMessage('Stránka smazána', 'success');
        $this->redirect(':Back:Page:Pages');

    }



    protected function createComponentNewPageForm($name)
    {
        $form = new \SRS\Form\CMS\NewPageForm();
        return $form;
    }


    protected function createComponentPageForm($name) {
        $pageId = $this->getParameter('id');
        $area = $this->getParameter('area');
        /** @var \SRS\Model\CMS\Page $page */
        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
        $roles = $this->context->database->getRepository('\SRS\Model\Acl\Role')->findAll();
        $roleChoices = \SRS\Form\EntityForm::getFormChoices($roles);
        if ($page == null) throw new \Nette\Application\BadRequestException('Stránka s tímto id neexistuje');
        $form = new \SRS\Form\CMS\PageForm(null, null, $roleChoices);
        $form->bindEntity($page);

        foreach ($page->getContents($area) as $content) {
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
        $area = $this->getParameter('area');

        $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);

        $page->setProperties($values, $this->context->database);

        foreach ($page->getContents($area) as $content) {

            $contentFormContainer = $values[$content->getFormIdentificator()];
            $deleteContent = $contentFormContainer['delete'];
            if ($deleteContent == true) $this->context->database->remove($content);
            else $content->setValuesFromPageForm($form);

        }
        if ($values['add_content'] != null) {
            $contentTypeStr = '\\SRS\\Model\\CMS\\'.$values['add_content'].'Content';
            $contentType = new $contentTypeStr();
            $contentType->page = $page;
            $contentType->area = $area;
            $this->context->database->persist($contentType);
            $page->contents->add($contentType); // TODO nefunguje fix
            $this->flashMessage("Obsah typu {$values['add_content']} přidán");
        }
        $this->context->database->flush();
        $this->flashMessage('Stránka uložena');

        $submitName = ($form->isSubmitted());
        $submitName = $submitName->htmlName;

        if ($submitName == 'submit_to_list') $this->redirect(':Back:Page:pages');
        else if ($submitName == 'submit_to_sidebar') {
            $this->redirect(':Back:Page:page#pageContents', array('id'=> $pageId, 'area' => 'sidebar'));
        }
        else if ($submitName == 'submit_to_main') {
            $this->redirect(':Back:Page:page#pageContents', array('id'=> $pageId, 'area' => 'main'));
        }
        else {
        $this->redirect('this');
        }

    }


}
