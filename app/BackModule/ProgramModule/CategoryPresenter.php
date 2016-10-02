<?php

namespace BackModule\ProgramModule;

/**
 * Obsluhuje sekci kategorie
 */
class CategoryPresenter extends \BackModule\BasePresenter
{
    protected $resource = \SRS\Model\Acl\Resource::CATEGORY;

    /**
     * @var \SRS\Model\Program\CategoryRepository
     */
    protected $categoryRepo;

    public function startup()
    {
        parent::startup();
        $this->checkPermissions(\SRS\Model\Acl\Permission::MANAGE);
        $this->categoryRepo = $this->context->database->getRepository('\SRS\Model\Program\Category');
    }

    public function beforeRender()
    {
        parent::beforeRender();
    }

    public function renderList()
    {
        $categories = $this->categoryRepo->findAll();
        $this->template->categories = $categories;
    }

    public function renderEdit($id = null)
    {
        if ($id != null) {
            $category = $this->categoryRepo->find($id);
            if ($category == null) throw new \Nette\Application\BadRequestException('Kategorie s tímto ID neexistuje', 404);
            $this['categoryForm']->bindEntity($category);
            $this->template->category = $category;
        }
    }

    public function handleDelete($id)
    {
        $category = $this->categoryRepo->find($id);
        if ($category == null) throw new \Nette\Application\BadRequestException('Místnost s tímto ID neexistuje', 404);
        $this->context->database->getRepository('\SRS\Model\Program\Block')->updateCategories($id, 'NULL');
        $this->context->database->remove($category);
        $this->context->database->flush();
        $this->flashMessage('Kategorie smazána', 'success');
        $this->redirect(":Back:Program:Category:list");
    }

    protected function createComponentCategoryForm()
    {
        return new \SRS\Form\Program\CategoryForm(null, null, $this->presenter->dbsettings, $this->context->database, $this->user);
    }

    protected function createComponentCategoryGrid()
    {
        return new \SRS\Components\CategoryGrid($this->context->database);
    }

}
