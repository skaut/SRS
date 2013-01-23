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

class FaqPresenter extends BasePresenter
{
    protected $entity = '\SRS\Model\CMS\Faq';

    public function startup() {
        parent::startup();

    }


    public function renderDefault() {
        $faq = $this->context->database->getRepository($this->entity)->findAllOrdered();
        $this->template->faq = $faq;
    }

    public function renderDetail($id) {
        if ($id != null) {
            $item = $this->context->database->getRepository($this->entity)->find($id);
            if ($item == null) throw new \Nette\Application\BadRequestException('Otázka s tímto id neexistuje', 404);
            $form = $this->getComponent('faqForm');
            $form->bindEntity($item);
            $this->template->item = $item;
        }


    }

    public function handleSort() {
        $itemOrder = $this->getParameter('items');
        $position = 0;
        foreach ($itemOrder as $itemId) {
            $item = $this->context->database->getRepository($this->entity)->find($itemId);
            $item->position = $position;
            $this->context->database->persist($item);
            $position++;
        }
        $this->context->database->flush();
        $this->flashMessage('Pořadí Otázek uloženo', 'success');
        $this->invalidateControl('faqlist');
        $this->invalidateControl('flashMessages');
    }

    public function handleDelete($id) {
        $item = $this->context->database->getRepository($this->entity)->find($id);
        if ($item == null) {
            throw new \Nette\Application\BadRequestException('Otázka s tímto id neexistuje', 404);
        }
        $this->presenter->context->database->remove($item);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Otázka smazána', 'success');
        $this->redirect('this');

    }


    protected function createComponentFaqForm() {
        return new \SRS\Form\CMS\FaqForm();
    }



}
