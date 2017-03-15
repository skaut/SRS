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
        parent::__construct();

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
        $grid->setDataSource($this->faqRepository->createQueryBuilder('f')->orderBy('f.position'));
        $grid->setPagination(false);


        $grid->addColumnText('question', 'admin.cms.faq_question');

        $grid->addColumnText('author', 'admin.cms.faq_author', 'author.displayName');

        $grid->addColumnStatus('public', 'admin.cms.faq_public')
            ->addOption(false, 'admin.cms.faq_public_private')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.cms.faq_public_public')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeStatus'];

        $grid->addColumnText('answered', 'admin.cms.faq_answered')
            ->setReplacement([
                false => $this->translator->translate('admin.common.no'),
                true => $this->translator->translate('admin.common.yes')
            ]);


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
        $faq = $this->faqRepository->findById($id);
        $this->faqRepository->remove($faq);

        $this->getPresenter()->flashMessage('admin.cms.faq_deleted', 'success');

        $this->redirect('this');
    }

    public function handleSort($item_id, $prev_id, $next_id)
    {
        $this->faqRepository->sort($item_id, $prev_id, $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['faqGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function changeStatus($id, $public)
    {
        $faq = $this->faqRepository->findById($id);
        $faq->setPublic($public);
        $this->faqRepository->save($faq);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq_changed_public', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['faqGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}