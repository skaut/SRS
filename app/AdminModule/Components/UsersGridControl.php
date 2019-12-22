<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Enums\SkautIsEventType;
use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\CustomInput\CustomText;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomFileValue;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use App\Services\SettingsService;
use App\Services\SkautIsEventEducationService;
use App\Services\SkautIsEventGeneralService;
use App\Services\SubeventService;
use App\Services\UserService;
use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use InvalidArgumentException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\ITranslator;
use Nette\Utils\Html;
use Nettrine\ORM\EntityManagerDecorator;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;
use function array_merge;
use function array_slice;
use function array_values;
use function explode;

/**
 * Komponenta pro správu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class UsersGridControl extends Control
{
    /** @var ITranslator */
    private $translator;

    /** @var EntityManagerDecorator */
    private $em;

    /** @var UserRepository */
    private $userRepository;

    /** @var SettingsService */
    private $settingsService;

    /** @var CustomInputRepository */
    private $customInputRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var Session */
    private $session;

    /** @var SessionSection */
    private $sessionSection;

    /** @var PdfExportService */
    private $pdfExportService;

    /** @var ExcelExportService */
    private $excelExportService;

    /** @var AclService */
    private $aclService;

    /** @var ApplicationService */
    private $applicationService;

    /** @var UserService */
    private $userService;

    /** @var SkautIsEventEducationService */
    private $skautIsEventEducationService;

    /** @var SkautIsEventGeneralService */
    private $skautIsEventGeneralService;

    /** @var SubeventService */
    private $subeventService;

    public function __construct(
        ITranslator $translator,
        EntityManagerDecorator $em,
        UserRepository $userRepository,
        SettingsService $settingsService,
        CustomInputRepository $customInputRepository,
        RoleRepository $roleRepository,
        PdfExportService $pdfExportService,
        ExcelExportService $excelExportService,
        Session $session,
        AclService $aclService,
        ApplicationService $applicationService,
        UserService $userService,
        SkautIsEventEducationService $skautIsEventEducationService,
        SkautIsEventGeneralService $skautIsEventGeneralService,
        SubeventService $subeventService
    ) {
        $this->translator                   = $translator;
        $this->em                           = $em;
        $this->userRepository               = $userRepository;
        $this->settingsService              = $settingsService;
        $this->customInputRepository        = $customInputRepository;
        $this->roleRepository               = $roleRepository;
        $this->pdfExportService             = $pdfExportService;
        $this->excelExportService           = $excelExportService;
        $this->aclService                   = $aclService;
        $this->applicationService           = $applicationService;
        $this->userService                  = $userService;
        $this->skautIsEventEducationService = $skautIsEventEducationService;
        $this->skautIsEventGeneralService   = $skautIsEventGeneralService;
        $this->subeventService              = $subeventService;

        $this->session        = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/users_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws SettingsException
     * @throws Throwable
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentUsersGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->userRepository->createQueryBuilder('u'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $grid->addGroupAction('admin.users.users_group_action_approve')
            ->onSelect[] = [$this, 'groupApprove'];

        $grid->addGroupMultiSelectAction(
            'admin.users.users_group_action_change_roles',
            $this->aclService->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED])
        )
            ->onSelect[] = [$this, 'groupChangeRoles'];

        $grid->addGroupAction('admin.users.users_group_action_mark_attended')
            ->onSelect[] = [$this, 'groupMarkAttended'];

        $grid->addGroupAction('admin.users.users_group_action_mark_paid_today', $this->preparePaymentMethodOptionsWithoutEmpty())
            ->onSelect[] = [$this, 'groupMarkPaidToday'];

        switch ($this->settingsService->getValue(Settings::SKAUTIS_EVENT_TYPE)) {
            case SkautIsEventType::GENERAL:
                $grid->addGroupAction('admin.users.users_group_action_insert_into_skaut_is')
                    ->onSelect[] = [$this, 'groupInsertIntoSkautIs'];
                break;

            case SkautIsEventType::EDUCATION:
                $grid->addGroupAction('admin.users.users_group_action_insert_into_skaut_is', $this->prepareInsertIntoSkautIsOptions())
                    ->onSelect[] = [$this, 'groupInsertIntoSkautIs'];
                break;

            default:
                throw new InvalidArgumentException();
        }

        $grid->addGroupAction('admin.users.users_group_action_generate_payment_proofs')
            ->onSelect[] = [$this, 'groupGeneratePaymentProofs'];

        $grid->addGroupAction('admin.users.users_group_action_export_users')
            ->onSelect[] = [$this, 'groupExportUsers'];

        $grid->addGroupAction('admin.users.users_group_action_export_subevents_and_categories')
            ->onSelect[] = [$this, 'groupExportSubeventsAndCategories'];

        $grid->addGroupAction('admin.users.users_group_action_export_roles')
            ->onSelect[] = [$this, 'groupExportRoles'];

        $grid->addGroupAction('admin.users.users_group_action_export_schedules')
            ->onSelect[] = [$this, 'groupExportSchedules'];

        $grid->addColumnText('displayName', 'admin.users.users_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('username', 'admin.users.users_username')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('roles', 'admin.users.users_roles', 'rolesText')
            ->setFilterMultiSelect($this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED]))
            ->setCondition(static function ($qb, $values) : void {
                $qb->join('u.roles', 'uR')
                    ->andWhere('uR.id IN (:rids)')
                    ->setParameter('rids', $values);
            });

        $grid->addColumnText('subevents', 'admin.users.users_subevents', 'subeventsText')
            ->setFilterMultiSelect($this->subeventService->getSubeventsOptions())
            ->setCondition(static function ($qb, $values) : void {
                $qb->join('u.applications', 'uA')
                    ->join('uA.subevents', 'uAS')
                    ->andWhere('uAS.id IN (:sids)')
                    ->andWhere('uA.validTo IS NULL')
                    ->andWhere('uA.state IN (:states)')
                    ->setParameter('sids', $values)
                    ->setParameter('states', [ApplicationState::PAID, ApplicationState::PAID_FREE, ApplicationState::WAITING_FOR_PAYMENT]);
            });

        $columnApproved  = $grid->addColumnStatus('approved', 'admin.users.users_approved');
        $columnApproved
            ->addOption(false, 'admin.users.users_approved_unapproved')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.users.users_approved_approved')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeApproved'];
        $columnApproved
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_approved_unapproved',
                '1' => 'admin.users.users_approved_approved',
            ])
            ->setTranslateOptions();

        $grid->addColumnText('unit', 'admin.users.users_membership')
            ->setRendererOnCondition(function (User $row) {
                return Html::el('span')
                    ->style('color: red')
                    ->setText($this->userService->getMembershipText($row));
            }, static function (User $row) {
                return $row->getUnit() === null;
            })
            ->setSortable()
            ->setSortableCallback(static function (QueryBuilder $qb, array $sort) : void {
                $sortOrig = $sort['unit'];
                $sortRev  = $sort['unit'] === 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.unit', $sortOrig)
                    ->addOrderBy('u.externalLector', $sortRev)
                    ->addOrderBy('u.member', $sortRev);
            })
            ->setFilterText();

        $grid->addColumnNumber('age', 'admin.users.users_age')
            ->setSortable()
            ->setSortableCallback(static function (QueryBuilder $qb, array $sort) : void {
                $sortRev = $sort['age'] === 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.birthdate', $sortRev);
            });

        $grid->addColumnText('email', 'admin.users.users_email')
            ->setRenderer(static function (User $row) {
                return Html::el('a')
                    ->href('mailto:' . $row->getEmail())
                    ->setText($row->getEmail());
            })
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('city', 'admin.users.users_city')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('fee', 'admin.users.users_fee')
            ->setSortable();

        $grid->addColumnNumber('feeRemaining', 'admin.users.users_fee_remaining')
            ->setSortable();

        $grid->addColumnText('variableSymbol', 'admin.users.users_variable_symbol', 'variableSymbolsText')
            ->setFilterText()
            ->setCondition(static function (QueryBuilder $qb, $value) : void {
                $qb->join('u.applications', 'uAVS')
                    ->join('uAVS.variableSymbol', 'uAVSVS')
                    ->andWhere('uAVSVS.variableSymbol LIKE :variableSymbol')
                    ->setParameter(':variableSymbol', $value . '%');
            });

        $grid->addColumnText('paymentMethod', 'admin.users.users_payment_method')
            ->setRenderer(function (User $user) {
                return $user->getPaymentMethod() ? $this->translator->translate('common.payment.' . $user->getPaymentMethod()) : '';
            })
            ->setFilterMultiSelect($this->preparePaymentMethodOptionsWithMixed())
            ->setTranslateOptions();

        $grid->addColumnDateTime('lastPaymentDate', 'admin.users.users_last_payment_date')
            ->setSortable();

        $grid->addColumnDateTime('rolesApplicationDate', 'admin.users.users_roles_application_date')
            ->setFormat(Helpers::DATETIME_FORMAT)
            ->setSortable();

        $columnAttended  = $grid->addColumnStatus('attended', 'admin.users.users_attended');
        $columnAttended
            ->addOption(false, 'admin.users.users_attended_no')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.users.users_attended_yes')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeAttended'];
        $columnAttended
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_attended_no',
                '1' => 'admin.users.users_attended_yes',
            ])
            ->setTranslateOptions();

        $grid->addColumnText('notRegisteredMandatoryBlocksCount', 'admin.users.users_not_registered_mandatory_blocks')
            ->setRenderer(static function (User $user) {
                return Html::el('span')
                    ->setAttribute('data-toggle', 'tooltip')
                    ->setAttribute('title', $user->getNotRegisteredMandatoryBlocksText())
                    ->setText($user->getNotRegisteredMandatoryBlocksCount());
            })
            ->setSortable();

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $columnCustomInputName = 'customInput' . $customInput->getId();

            $columnCustomInput = $grid->addColumnText($columnCustomInputName, Helpers::truncate($customInput->getName(), 20))
                ->setRenderer(function (User $user) use ($customInput) {
                    $customInputValue = $user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        switch (true) {
                            case $customInputValue instanceof CustomTextValue:
                                return Helpers::truncate($customInputValue->getValue(), 20);
                            case $customInputValue instanceof CustomCheckboxValue:
                                return $customInputValue->getValue()
                                    ? $this->translator->translate('admin.common.yes')
                                    : $this->translator->translate('admin.common.no');
                            case $customInputValue instanceof CustomSelectValue:
                                return $customInputValue->getValueOption();
                            case $customInputValue instanceof CustomFileValue:
                                return $customInputValue->getValue()
                                    ? Html::el('a')
                                        ->setAttribute('href', $this->getPresenter()->getTemplate()->basePath
                                            . '/files' . $customInputValue->getValue())
                                        ->setAttribute('title', array_values(array_slice(explode('/', $customInputValue->getValue()), -1))[0])
                                        ->setAttribute('target', '_blank')
                                        ->setAttribute('class', 'btn btn-xs btn-default')
                                        ->addHtml(
                                            Html::el('span')->setAttribute('class', 'fa fa-download')
                                        )
                                    : '';
                        }
                    }

                    return null;
                });

            switch (true) {
                case $customInput instanceof CustomText:
                    $columnCustomInput->setSortable()
                        ->setSortableCallback(static function (QueryBuilder $qb, array $sort) use ($customInput, $columnCustomInputName) : void {
                            $qb->leftJoin('u.customInputValues', 'uCIV1')
                                ->leftJoin('uCIV1.input', 'uCIVI1')
                                ->leftJoin('App\Model\User\CustomInputValue\CustomTextValue', 'uCTV', 'WITH', 'uCIV1.id = uCTV.id')
                                ->andWhere('uCIVI1.id = :iid1 OR uCIVI1.id IS NULL')
                                ->setParameter('iid1', $customInput->getId())
                                ->orderBy('uCTV.value', $sort[$columnCustomInputName]);
                        });
                    break;

                case $customInput instanceof CustomCheckbox:
                    $columnCustomInput->setFilterSelect(['' => 'admin.common.all', 1 => 'admin.common.yes', 0 => 'admin.common.no'])
                        ->setCondition(static function (QueryBuilder $qb, string $value) use ($customInput) : void {
                            if ($value === '') {
                                return;
                            } else {
                                $qb->leftJoin('u.customInputValues', 'uCIV2')
                                    ->leftJoin('uCIV2.input', 'uCIVI2')
                                    ->leftJoin('App\Model\User\CustomInputValue\CustomCheckboxValue', 'uCCV', 'WITH', 'uCIV2.id = uCCV.id')
                                    ->andWhere('uCIVI2.id = :iid2 OR uCIVI2.id IS NULL')
                                    ->andWhere('uCCV.value = :ivalue2')
                                    ->setParameter('iid2', $customInput->getId())
                                    ->setParameter('ivalue2', $value);
                            }
                        })
                        ->setTranslateOptions();
                    break;

                case $customInput instanceof CustomSelect:
                    $columnCustomInput->setFilterSelect(array_merge(['' => 'admin.common.all'], $customInput->getSelectOptions()))
                        ->setCondition(static function (QueryBuilder $qb, string $value) use ($customInput) : void {
                            if ($value === '') {
                                return;
                            } else {
                                $qb->leftJoin('u.customInputValues', 'uCIV3')
                                    ->leftJoin('uCIV3.input', 'uCIVI3')
                                    ->leftJoin('App\Model\User\CustomInputValue\CustomSelectValue', 'uCSV', 'WITH', 'uCIV3.id = uCSV.id')
                                    ->andWhere('uCIVI3.id = :iid3 OR uCIVI3.id IS NULL')
                                    ->andWhere('uCSV.value = :ivalue3')
                                    ->setParameter('iid3', $customInput->getId())
                                    ->setParameter('ivalue3', $value);
                            }
                        })
                        ->setTranslateOptions();
                    break;
            }
        }

        $grid->addColumnText('note', 'admin.users.users_private_note')
            ->setFilterText();

        $grid->addToolbarButton('Users:add')
            ->setIcon('plus')
            ->setText('admin.users.users_add_lector');

        $grid->addAction('detail', 'admin.common.detail', 'Users:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.users.users_delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', static function (User $item) {
            return $item->isExternalLector();
        });

        $grid->setColumnsSummary(['fee', 'feeRemaining']);
    }

    /**
     * Zpracuje odstranění externího uživatele.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function handleDelete(int $id) : void
    {
        $user = $this->userRepository->findById($id);

        $this->userRepository->remove($user);

        $this->getPresenter()->flashMessage('admin.users.users_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Změní stav uživatele.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function changeApproved(int $id, bool $approved) : void
    {
        $user = $this->userRepository->findById($id);
        $user->setApproved($approved);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_approved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            /** @var DataGrid $usersGrid */
            $usersGrid = $this['usersGrid'];
            $usersGrid->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Změní účast uživatele na semináři.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function changeAttended(int $id, bool $attended) : void
    {
        $user = $this->userRepository->findById($id);
        $user->setAttended($attended);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            /** @var DataGrid $usersGrid */
            $usersGrid = $this['usersGrid'];
            $usersGrid->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Hromadně schválí uživatele.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function groupApprove(array $ids) : void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $this->em->transactional(function () use ($users) : void {
            foreach ($users as $user) {
                $user->setApproved(true);
                $this->userRepository->save($user);
            }
        });

        $this->getPresenter()->flashMessage('admin.users.users_group_action_approved', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně nastaví role.
     *
     * @param int[] $ids
     * @param int[] $value
     *
     * @throws Throwable
     */
    public function groupChangeRoles(array $ids, array $value) : void
    {
        $users         = $this->userRepository->findUsersByIds($ids);
        $selectedRoles = $this->roleRepository->findRolesByIds($value);

        $p = $this->getPresenter();

        //neni vybrana zadna role
        if ($selectedRoles->isEmpty()) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_empty', 'danger');
            $this->redirect('this');
        }

        //v rolich musi byt dostatek volnych mist
        $capacitiesOk = $selectedRoles->forAll(static function (int $key, Role $role) use ($users) {
            if (! $role->hasLimitedCapacity()) {
                return true;
            }

            $capacityNeeded = $users->count();

            if ($capacityNeeded <= $role->getCapacity()) {
                return true;
            }

            foreach ($users as $user) {
                if (! $user->isInRole($role)) {
                    continue;
                }

                $capacityNeeded--;
            }

            return $capacityNeeded <= $role->getCapacity();
        });

        if (! $capacitiesOk) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_capacity', 'danger');
            $this->redirect('this');
        }

        $loggedUser = $this->userRepository->findById($p->getUser()->id);

        $this->em->transactional(function () use ($selectedRoles, $users, $loggedUser) : void {
            foreach ($users as $user) {
                $this->applicationService->updateRoles($user, $selectedRoles, $loggedUser, true);
            }
        });

        $p->flashMessage('admin.users.users_group_action_changed_roles', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně označí uživatele jako zúčastněné.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function groupMarkAttended(array $ids) : void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $this->em->transactional(function () use ($users) : void {
            foreach ($users as $user) {
                $user->setAttended(true);
                $this->userRepository->save($user);
            }
        });

        $this->getPresenter()->flashMessage('admin.users.users_group_action_marked_attended', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně označí uživatele jako zaplacené dnes.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function groupMarkPaidToday(array $ids, string $paymentMethod) : void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $p = $this->getPresenter();

        $loggedUser = $this->userRepository->findById($p->getUser()->id);

        $this->em->transactional(function () use ($users, $paymentMethod, $loggedUser) : void {
            foreach ($users as $user) {
                foreach ($user->getWaitingForPaymentApplications() as $application) {
                    $this->applicationService->updateApplicationPayment(
                        $application,
                        $paymentMethod,
                        new DateTimeImmutable(),
                        $application->getIncomeProofPrintedDate(),
                        $application->getMaturityDate(),
                        $loggedUser
                    );
                }
            }
        });

        $p->flashMessage('admin.users.users_group_action_marked_paid_today', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně vloží uživatele jako účastníky do skautIS.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function groupInsertIntoSkautIs(array $ids, bool $accept) : void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $p = $this->getPresenter();

        $eventId = $this->settingsService->getIntValue(Settings::SKAUTIS_EVENT_ID);

        if ($eventId === null) {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_not_connected', 'danger');
            $this->redirect('this');
        }

        switch ($this->settingsService->getValue(Settings::SKAUTIS_EVENT_TYPE)) {
            case SkautIsEventType::GENERAL:
                $skautIsEventService = $this->skautIsEventGeneralService;
                break;

            case SkautIsEventType::EDUCATION:
                $skautIsEventService = $this->skautIsEventEducationService;
                if (! $skautIsEventService->isSubeventConnected()) {
                    $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_subevent_not_connected', 'danger');
                    $this->redirect('this');
                }
                break;

            default:
                throw new InvalidArgumentException();
        }

        if (! $skautIsEventService->isEventDraft($eventId)) {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_not_draft', 'danger');
            $this->redirect('this');
        }

        if ($skautIsEventService->insertParticipants($eventId, $users, $accept ?: false)) {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_successful', 'success');
        } else {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_skaut_is', 'danger');
        }

        $this->redirect('this');
    }

    /**
     * Hromadně vygeneruje potvrzení o zaplacení.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupGeneratePaymentProofs(array $ids) : void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('generatepaymentproofs'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Hromadně vyexportuje seznam uživatelů.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportUsers(array $ids) : void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportusers'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export seznamu uživatelů.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportUsers() : void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersList($users, 'seznam-uzivatelu.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje seznam uživatelů s rolemi.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportRoles(array $ids) : void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportroles'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export seznamu uživatelů s rolemi.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportRoles() : void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);
        $roles = $this->roleRepository->findAll();

        $response = $this->excelExportService->exportUsersRoles($users, new ArrayCollection($roles), 'role-uzivatelu.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje seznam uživatelů s podakcemi a programy podle kategorií.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportSubeventsAndCategories(array $ids) : void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportsubeventsandcategories'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export seznamu uživatelů s podakcemi a programy podle kategorií.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportSubeventsAndCategories() : void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSubeventsAndCategories($users, 'podakce-a-kategorie.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje harmonogramy uživatelů.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportSchedules(array $ids) : void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportschedules'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export harmonogramů uživatelů.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportSchedules() : void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSchedules($users, 'harmonogramy-uzivatelu.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Vygeneruje doklady o zaplacení.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function handleGeneratePaymentProofs() : void
    {
        $ids   = $this->session->getSection('srs')->userIds;
        $users = $this->userRepository->findUsersByIds($ids);
        $this->pdfExportService->generateUsersPaymentProofs(
            $users,
            'doklady.pdf',
            $this->userRepository->findById($this->getPresenter()->getUser()->id)
        );
    }

    /**
     * Vrátí platební metody jako možnosti pro select. Bez prázdné možnosti.
     *
     * @return string[]
     */
    private function preparePaymentMethodOptionsWithoutEmpty() : array
    {
        $options = [];
        foreach (PaymentType::$types as $type) {
            $options[$type] = 'common.payment.' . $type;
        }

        return $options;
    }

    /**
     * Vrátí platební metody jako možnosti pro select. Včetně smíšené.
     *
     * @return string[]
     */
    private function preparePaymentMethodOptionsWithMixed() : array
    {
        $options = [];
        foreach (PaymentType::$types as $type) {
            $options[$type] = 'common.payment.' . $type;
        }
        $options[PaymentType::MIXED] = 'common.payment.' . PaymentType::MIXED;

        return $options;
    }

    /**
     * Vrátí možnosti vložení účastníků do vzdělávací akce skautIS.
     *
     * @return string[]
     */
    private function prepareInsertIntoSkautIsOptions() : array
    {
        $options        = [];
        $options[false] = 'common.skautis_event_insert_type.registered';
        $options[true]  = 'common.skautis_event_insert_type.accepted';

        return $options;
    }
}
