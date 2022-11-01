<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\User\Patrol;
use App\Model\User\Troop;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Repositories\UserGroupRoleRepository;
use App\Services\AclService;
use Doctrine\Common\Collections\ArrayCollection;
use App\Services\ApplicationService;
use App\Services\ExcelExportService;
use App\Services\QueryBus;
use App\Services\SkautIsEventEducationService;
use App\Services\SkautIsEventGeneralService;
use App\Services\SubeventService;
use App\Services\UserService;
use App\Utils\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use Throwable;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

use function count;

/**
 * Komponenta pro zobrazení datagridu družin.
 */
class GroupsGridControl extends Control
{
    private SessionSection $sessionSection;

    public function __construct(
        private QueryBus $queryBus,
        private Translator $translator,
        private EntityManagerInterface $em,
        private TroopRepository $repository,
        private CustomInputRepository $customInputRepository,
        private RoleRepository $roleRepository,
        private ExcelExportService $excelExportService,
        private Session $session,
        private AclService $aclService,
        private ApplicationService $applicationService,
        private UserService $userService,
        private SkautIsEventEducationService $skautIsEventEducationService,
        private SkautIsEventGeneralService $skautIsEventGeneralService,
        private SubeventService $subeventService
    ) {
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/groups_grid.latte');
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
        $grid->setDataSource($this->repository->createQueryBuilder('p'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $grid->addGroupAction('Export seznamu skupin')
            ->onSelect[] = [$this, 'groupExportUsers'];

        $grid->addColumnText('id', 'ID')
            ->setSortable();

        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('variableSymbol', 'VS ')->setSortable() // je stejný jako název skupiny
		->setRenderer(static fn ($t) => $t->getVariableSymbol()->getVariableSymbol())
->setFilterText();


           $grid->addColumnText('leader', 'Vedoucí')->setSortable()
            ->setRenderer( function (Troop $t) {
		$leader=$t->getLeader();
			return Html::el("a")->setAttribute("href",$this->getPresenter()->link("detail",$leader->getId()))->setText($leader->getDisplayName());

})
            ->setFilterText();

        $grid->addColumnDateTime('created', 'Datum založení')
            ->setRenderer(static function (Troop $p) {
                $date = $p->getApplicationDate();

                return $date ? $date->format(Helpers::DATETIME_FORMAT) : '';
            })
            ->setSortable();

        $grid->addColumnText('pairingCode', 'Kód Jamoddílu')->setFilterText();

        $grid->addColumnText('fee', 'Cena getFee')->setSortable()->setFilterText();

        $grid->addColumnText('fee2', 'Cena countFee')->setSortable()
		->setRenderer (static fn (Troop $t) => $t->countFee());

        $grid->addColumnDateTime('paidDate', 'Datum zaplacení')
            ->setRenderer(static function (Troop $p) {
                $date = $p->getPaymentDate();

                return $date ? $date->format(Helpers::DATETIME_FORMAT) : '';
            })
            ->setSortable();

        $grid->addColumnDateTime('mDate', 'Datum splatnosti')
            ->setRenderer(static function (Troop $p) {
                $date = $p->getmaturityDate();

                return $date ? $date->format(Helpers::DATETIME_FORMAT) : '';
            })
            ->setSortable();




//        $grid->addColumnText('troop', 'Oddíl - přidat link')
//            ->setRenderer( function (Patrol $p) { $troop = $p->getTroop();
//			return Html::el("a")->setAttribute("href",$this->link("troopDetail",$troop->getId()))->setText($troop->getName());

//}); // link na oddíl

        $grid->addColumnText('numPatrols', '# družin')
            ->setRenderer(static fn (Troop $p) => count($p->getPatrols())); // je to správné číslo?

//        $grid->addColumnText('notRegisteredMandatoryBlocksCount', 'admin.users.users_not_registered_mandatory_blocks')
//            ->setRenderer(static function (User $user) {
//                return Html::el('span')
//                    ->setAttribute('data-toggle', 'tooltip')
//                    ->setAttribute('title', $user->getNotRegisteredMandatoryBlocksText())
//                    ->setText($user->getNotRegisteredMandatoryBlocksCount());
//            })
//            ->setSortable();


        $grid->addAction('detail', 'admin.common.detail', 'Users:groupDetail') // destinace
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.users.users_delete_confirm'),
            ]);

        return $grid;
    }

    /**
     * Zpracuje odstranění externí skupiny.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $rec = $this->repository->findById($id);

        $this->repository->remove($rec);

        $p = $this->getPresenter();
        $p->flashMessage('Skupina smazána.', 'success');
        $p->redirect('this');
    }

    /**
     * Hromadně vyexportuje seznam družin.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportUsers(array $ids): void
    {
        $this->sessionSection->patrolIds = $ids;
        $this->redirect('exportusers');
    }

    /**
     * Zpracuje export seznamu družin.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportUsers(): void
    {
        $ids = $this->session->getSection('srs')->patrolIds;

        $res = $this->repository->createQueryBuilder('p')->where('p.id IN (:ids)') // stejne se v teto class querybuilder pouziva
        ->setParameter('ids', $ids)->getQuery()->getResult(); // otestovat , podivat se na vzor (export uzivatelu)

	$users = new ArrayCollection($res);
        $response = $this->excelExportService->exportUsersList($users, 'seznam-uzivatelu.xlsx'); // nutna nova metoda

        $this->getPresenter()->sendResponse($response);
    }
}
