<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Components;

use App\Model\Acl\Role;
use App\Model\User\Commands\RemoveTroop;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Troop;
use App\Services\CommandBus;
use App\Services\ExcelExportService;
use App\Utils\Helpers;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

use function count;

/**
 * Komponenta pro zobrazení datagridu družin.
 */
class TroopsGridControl extends Control
{
    private SessionSection $sessionSection;

    public function __construct(
        private CommandBus $commandBus,
        private Translator $translator,
        private TroopRepository $troopRepository,
        private ExcelExportService $excelExportService,
        private Session $session
    ) {
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/troops_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws Throwable
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentPatrolsGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->troopRepository->createQueryBuilder('p'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $grid->addGroupAction('Export seznamu skupin')
            ->onSelect[] = [$this, 'groupExportTroops'];

        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('state', 'Stav')
            ->setSortable()
            ->setRenderer(fn ($t) => $this->translator->translate('common.application_state.' . $t->getState()))
            ->setFilterText();

        $grid->addColumnText('variableSymbol', 'Variabilní symbol', 'variableSymbolText')
            ->setSortable()
            ->setSortableCallback(static function (QueryBuilder $qb, array $sort): void {
                $sortRev = $sort['variableSymbolText'] === 'DESC' ? 'DESC' : 'ASC';
                $qb->join('p.variableSymbol', 'pVS')
                    ->orderBy('pVS.variableSymbol', $sortRev);
            })
            ->setFilterText()
            ->setCondition(static function (QueryBuilder $qb, string $value): void {
                $qb->join('p.variableSymbol', 'pVS')
                    ->andWhere('pVS.variableSymbol LIKE :variableSymbol')
                    ->setParameter(':variableSymbol', '%' . $value . '%');
            });

        $grid->addColumnText('leader', 'Vedoucí')
            ->setRenderer(function (Troop $t) {
                $leader = $t->getLeader();

                return Html::el('a')->setAttribute('href', $this->getPresenter()->link('Users:detail', $leader->getId()))->setText($leader->getDisplayName());
            });

        $grid->addColumnText('leaderEmail', 'E-mail vedoucího')
            ->setRenderer(static function (Troop $t) {
                $email = $t->getLeader()->getEmail();

                return Html::el('a')->href('mailto:' . $email)->setText($email);
            });

        $grid->addColumnDateTime('applicationDate', 'Datum založení')
            ->setRenderer(static function (Troop $t) {
                $date = $t->getApplicationDate();

                return $date ? $date->format(Helpers::DATETIME_FORMAT) : '';
            })
            ->setSortable();

        $grid->addColumnNumber('fee', 'Cena')->setSortable()->setFilterText();

        $grid->addColumnDateTime('maturityDate', 'Datum splatnosti')
            ->setFormat(Helpers::DATE_FORMAT)
            ->setSortable();

        $grid->addColumnDateTime('paymentDate', 'Datum platby')
            ->setFormat(Helpers::DATE_FORMAT)
            ->setSortable();

        $grid->addColumnText('pairingCode', 'Kód jamoddílu')
            ->setFilterText();

        $grid->addColumnNumber('numPersons', '# osob')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => $p->countUsersInRoles([Role::PATROL_LEADER, Role::LEADER, Role::ESCORT, Role::ATTENDEE]));

        $grid->addColumnNumber('numChilder', '# rádců')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => $p->countUsersInRoles([Role::PATROL_LEADER]));

        $grid->addColumnNumber('numAdults', '# dospělých')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => $p->countUsersInRoles([Role::LEADER, Role::ESCORT]));

        $grid->addColumnNumber('numPatrols', '# družin')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => count($p->getConfirmedPatrols()));

        $grid->addAction('generatePaymentProof', 'Stáhnout potvzrení o přijetí platby', 'generatePaymentProof');
        $grid->allowRowsAction('generatePaymentProof', static fn (Troop $troop) => $troop->getPaymentDate() !== null);

        $grid->addAction('detail', 'admin.common.detail', 'Troops:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('Opravdu chcete skupinu odstranit?'),
            ]);

        return $grid;
    }

    /**
     * Zpracuje odstranění skupiny.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $troop = $this->troopRepository->findById($id);
        $this->commandBus->handle(new RemoveTroop($troop));
        $p = $this->getPresenter();
        $p->flashMessage('Skupina byla úspěšně odstraněna.', 'success');
        $p->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     *
     * @throws AbortException
     */
    public function handleGeneratePaymentProof(int $id): void
    {
        $this->presenter->redirect(':Export:TroopIncomeProof:troop', ['id' => $id]);
    }

    /**
     * Hromadně vyexportuje seznam skupin.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportTroops(array $ids): void
    {
        $this->sessionSection->troopIds = $ids;
        $this->redirect('exporttroops');
    }

    /**
     * Zpracuje export seznamu skupin.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportTroops(): void
    {
        $ids = $this->session->getSection('srs')->troopIds;

        $troops = $this->troopRepository->findTroopsByIds($ids);

        $response = $this->excelExportService->exportTroopsList($troops, 'seznam-skupin.xlsx');

        $this->getPresenter()->sendResponse($response);
    }
}
