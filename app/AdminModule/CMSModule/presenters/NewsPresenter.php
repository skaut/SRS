<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\INewsGridControlFactory;
use App\AdminModule\CMSModule\Forms\NewsFormFactory;

class NewsPresenter extends CMSBasePresenter
{
    /**
     * @var INewsGridControlFactory
     * @inject
     */
    public $newsGridControlFactory;

    /**
     * @var NewsFormFactory
     * @inject
     */
    public $newsFormFactory;

    public function renderAdd() {
        $this['newsForm']->setDefaults([
            'published' => new \DateTime()
        ]);
    }

    public function renderEdit($id) {

    }

    protected function createComponentNewsGrid($name)
    {
        return $this->newsGridControlFactory->create($name);
    }

    protected function createComponentNewsForm($name)
    {
        $form =  $this->newsFormFactory->create();

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