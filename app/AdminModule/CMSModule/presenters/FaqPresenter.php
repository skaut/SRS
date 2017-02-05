<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\IFaqGridControlFactory;
use App\AdminModule\CMSModule\Forms\FaqFormFactory;

class FaqPresenter extends CMSBasePresenter
{
    /**
     * @var IFaqGridControlFactory
     * @inject
     */
    public $faqGridControlFactory;

    /**
     * @var FaqFormFactory
     * @inject
     */
    public $faqFormFactory;

    public function renderAdd() {
        $this['faqForm']->setDefaults([
            'status' => true
        ]);
    }

    public function renderEdit($id) {

    }

    protected function createComponentFaqGrid($name)
    {
        return $this->faqGridControlFactory->create($name);
    }

    protected function createComponentFaqForm()
    {
        $form =  $this->faqFormFactory->create();

//        $form->onSuccess[] = function (Form $form, \stdClass $values) {
//            if ($form['submitAndContinue']->isSubmittedBy()) {
//                $block = $this->saveBlock($values);
//                $this->redirect('Blocks:edit', ['id' => $block->getId()]);
//            }
//            else {
//                $this->saveBlock($values);
//                $this->redirect('Blocks:default');
//            }
//        };

        return $form;
    }
}