<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Utils\Helpers;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu podakcí.
 */
class SubeventsGridControl extends Control
{
    public function __construct(private readonly Translator $translator, private readonly SubeventRepository $subeventRepository)
    {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/subevents_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentSubeventsGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->subeventRepository->createQueryBuilder('s'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.configuration.subevents_name');

        $grid->addColumnText('implicit', 'admin.configuration.subevents_implicit')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes'),
            ]);

        $grid->addColumnDateTime('registerableFrom', 'admin.configuration.subevents.registerable_from')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnDateTime('registerableTo', 'admin.configuration.subevents.registerable_to')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnNumber('fee', 'admin.configuration.subevents_fee');

        $grid->addColumnText('capacity', 'admin.configuration.subevents_occupancy', 'occupancy_text');

        $grid->addToolbarButton('Subevents:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', 'admin.common.edit', 'Subevents:edit');
        $grid->allowRowsAction('edit', static fn (Subevent $item) => ! $item->isImplicit());

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.configuration.subevents_delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', static fn (Subevent $item) => ! $item->isImplicit());
    }

    /**
     * Zpracuje odstranění podakce.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $subevent = $this->subeventRepository->findById($id);

        $p = $this->getPresenter();

        if ($subevent->getBlocks()->isEmpty()) {
            $this->subeventRepository->remove($subevent);
            $p->flashMessage('admin.configuration.subevents_deleted', 'success');
        } else {
            $p->flashMessage('admin.configuration.subevents_deleted_error', 'danger');
        }

        $p->redirect('this');
    }
}
