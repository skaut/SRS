<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Presenters;

use App\AdminModule\CMSModule\Components\FaqGridControl;
use App\AdminModule\CMSModule\Components\IFaqGridControlFactory;
use App\AdminModule\CMSModule\Forms\FaqFormFactory;
use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\FaqRepository;
use stdClass;

/**
 * Presenter starající se o správu častých otázek.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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

    /**
     * @var FaqRepository
     * @inject
     */
    public $faqRepository;

    public function renderEdit(int $id) : void
    {
    }

    protected function createComponentFaqGrid() : FaqGridControl
    {
        return $this->faqGridControlFactory->create();
    }

    protected function createComponentFaqForm() : BaseForm
    {
        $form = $this->faqFormFactory->create((int) $this->getParameter('id'), $this->user->id);

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            if ($form->isSubmitted() === $form['cancel']) {
                $this->redirect('Faq:default');
            }

            $this->flashMessage('admin.cms.faq_saved', 'success');

            if ($form->isSubmitted() === $form['submitAndContinue']) {
                $id = $values->id ?: $this->faqRepository->findLastId();
                $this->redirect('Faq:edit', ['id' => $id]);
            } else {
                $this->redirect('Faq:default');
            }
        };

        return $form;
    }
}
