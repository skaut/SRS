<?php
/**
 * Date: 30.10.12
 * Time: 21:16
 * Author: Michal Májský
 */
namespace BackModule\CMSModule;
use Nette\Application\UI\Form;
use SRS\Model\Acl\Resource;
use SRS\Model\Acl\Permission;

class NewsPresenter extends \BackModule\BasePresenter
{
    protected $resource = Resource::CMS;
    protected $entity = '\SRS\Model\CMS\News';

    public function startup()
    {
        parent::startup();
        $this->checkPermissions(Permission::MANAGE);

    }


    public function renderDefault()
    {
        $news = $this->context->database->getRepository($this->entity)->findAllOrderedByDate();
        $this->template->news = $news;
    }

    public function renderDetail($id)
    {
        $form = $this->getComponent('newsForm');
        if ($id != null) {
            $item = $this->context->database->getRepository($this->entity)->find($id);
            if ($item == null) throw new \Nette\Application\BadRequestException('Aktualita s tímto id neexistuje', 404);
            $form = $this->getComponent('newsForm');
            $form->bindEntity($item);
            $this->template->item = $item;
        } else {
            $today = new \DateTime('now');
            $today = $today->format('Y-m-d');
            $form['published']->setDefaultValue($today);
        }


    }


    public function handleDelete($id)
    {
        $item = $this->context->database->getRepository($this->entity)->find($id);
        if ($item == null) {
            throw new \Nette\Application\BadRequestException('Aktualita s tímto id neexistuje', 404);
        }
        $this->presenter->context->database->remove($item);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Aktualita smazána', 'success');
        $this->redirect('this');

    }


    protected function createComponentNewsForm()
    {
        return new \SRS\Form\CMS\NewsForm();
    }


}
