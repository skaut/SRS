<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Components;

use App\Model\CMS\FaqRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu častých otázek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var FaqRepository */
    private $faqRepository;


    public function __construct(Translator $translator, FaqRepository $faqRepository)
    {
        parent::__construct();

        $this->translator    = $translator;
        $this->faqRepository = $faqRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/faq_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentFaqGrid(string $name) : void
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
                true => $this->translator->translate('admin.common.yes'),
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
                'data-content' => $this->translator->translate('admin.cms.faq_delete_confirm'),
            ]);
    }

    /**
     * Zpracuje odstranění otázky.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function handleDelete(int $id) : void
    {
        $faq = $this->faqRepository->findById($id);
        $this->faqRepository->remove($faq);

        $this->getPresenter()->flashMessage('admin.cms.faq_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Přesuee otázku $item_id mezi $prev_id a $next_id.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function handleSort($item_id, $prev_id, $next_id) : void
    {
        $this->faqRepository->sort((int) $item_id, (int) $prev_id, (int) $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['faqGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Změní viditelnost otázky.
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function changeStatus(int $id, bool $public) : void
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
