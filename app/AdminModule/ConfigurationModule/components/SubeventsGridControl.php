<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Program\BlockRepository;
use App\Model\Structure\SubeventRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var BlockRepository */
    private $blockRepository;


    /**
     * SubeventsGridControl constructor.
     * @param Translator $translator
     * @param SubeventRepository $subeventRepository
     * @param BlockRepository $blockRepository
     */
    public function __construct(Translator $translator, SubeventRepository $subeventRepository,
                                BlockRepository $blockRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->subeventRepository = $subeventRepository;
        $this->blockRepository = $blockRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/subevents_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentSubeventsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->subeventRepository->createQueryBuilder('s'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.configuration.subevents_name');

        $grid->addColumnText('implicit', 'admin.configuration.subevents_implicit')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes')
            ]);

        $grid->addColumnNumber('fee', 'admin.configuration.subevents_fee');

        $grid->addColumnText('capacity', 'admin.configuration.subevents_occupancy', 'occupancy_text');

        $grid->addToolbarButton('Subevents:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', 'admin.common.edit', 'Subevents:edit');
        $grid->allowRowsAction('edit', function ($item) {
            return !$item->isImplicit();
        });

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.configuration.subevents_delete_confirm')
            ]);
        $grid->allowRowsAction('delete', function ($item) {
            return !$item->isImplicit();
        });
    }

    /**
     * Zpracuje odstranění podakce.
     * @param $id
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $subevent = $this->subeventRepository->findById($id);

        if ($subevent->getBlocks()->isEmpty()) {
            $this->subeventRepository->remove($subevent);
            $this->getPresenter()->flashMessage('admin.configuration.subevents_deleted', 'success');
        } else {
            $this->getPresenter()->flashMessage('admin.configuration.subevents_deleted_error', 'danger');
        }

        $this->redirect('this');
    }
}
