<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Cms\Repositories\FaqRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu častých otázek.
 */
class FaqGridControl extends Control
{
    public function __construct(private Translator $translator, private FaqRepository $faqRepository)
    {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/faq_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentFaqGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setSortable();
        $grid->setSortableHandler('faqGrid:sort!');
        $grid->setDataSource($this->faqRepository->createQueryBuilder('f')->orderBy('f.position'));
        $grid->setPagination(false);

        $grid->addColumnText('question', 'admin.cms.faq.common.question');

        $grid->addColumnText('author', 'admin.cms.faq.column.author', 'author.displayName');

        $grid->addColumnStatus('public', 'admin.cms.faq.column.public')
            ->addOption(false, 'admin.cms.faq.column.public_private')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.cms.faq.column.public_public')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeStatus'];

        $grid->addColumnText('answered', 'admin.cms.faq.column.answered')
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
                'data-content' => $this->translator->translate('admin.cms.faq.action.delete_confirm'),
            ]);

        return $grid;
    }

    /**
     * Zpracuje odstranění otázky.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $faq = $this->faqRepository->findById($id);
        $this->faqRepository->remove($faq);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq.message.delete_success', 'success');
        $p->redirect('this');
    }

    /**
     * Přesuene otázku $item_id mezi $prev_id a $next_id.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function handleSort(?string $item_id, ?string $prev_id, ?string $next_id): void
    {
        $this->faqRepository->sort((int) $item_id, (int) $prev_id, (int) $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq.message.order_save_success', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('faqGrid')->reload();
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Změní viditelnost otázky.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws AbortException
     */
    public function changeStatus(string $id, string $public): void
    {
        $faq = $this->faqRepository->findById((int) $id);
        $faq->setPublic((bool) $public);
        $this->faqRepository->save($faq);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.faq.message.public_change_success', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('faqGrid')->redrawItem($id);
        } else {
            $p->redirect('this');
        }
    }
}
