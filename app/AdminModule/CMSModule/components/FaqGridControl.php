<?php

namespace App\AdminModule\CMSModule\Components;


use App\Model\CMS\FaqRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class FaqGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var FaqRepository
     */
    private $faqRepository;

    public function __construct(Translator $translator, FaqRepository $faqRepository)
    {
        $this->translator = $translator;
        $this->faqRepository = $faqRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/faq_grid.latte');
    }

    public function createComponentFaqGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setSortable();
        $grid->setSortableHandler('faqGrid:sort!');
        $grid->setDataSource($this->faqRepository->createQueryBuilder('f'));
        $grid->setDefaultSort(['position' => 'ASC']);
        $grid->setPagination(false);


        $grid->addColumnText('question', 'admin.cms.faq_question');

        $grid->addColumnText('author', 'admin.cms.faq_author');

        $grid->addColumnStatus('status', 'admin.cms.faq_status');

        $grid->addColumnText('answered', 'admin.cms.faq_answered');


        $grid->addToolbarButton('Faq:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', 'admin.common.edit', 'Faq:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.faq_delete_confirm')
            ]);
    }

    public function handleDelete($id)
    {
        $this->faqRepository->removeQuestion($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq_deleted', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['newsGrid']->reload();
        }
        else {
            $this->redirect('this');
        }
    }

    public function handleSort($item_id, $prev_id, $next_id)
    {
        $this->faqRepository->changePosition($item_id, $prev_id, $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['newsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }
}