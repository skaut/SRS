<?php

namespace App\AdminModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\PaymentType;
use App\Model\Mailing\Mail;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ExcelExportService;
use App\Services\MailService;
use App\Services\PdfExportService;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var UserRepository */
    private $userRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var CustomInputRepository */
    private $customInputRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var Session */
    private $session;

    /** @var SessionSection */
    private $sessionSection;

    /** @var PdfExportService */
    private $pdfExportService;

    /** @var ExcelExportService */
    private $excelExportService;

    /** @var MailService */
    private $mailService;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;


    /**
     * UsersGridControl constructor.
     * @param Translator $translator
     * @param UserRepository $userRepository
     * @param SettingsRepository $settingsRepository
     * @param CustomInputRepository $customInputRepository
     * @param RoleRepository $roleRepository
     * @param ProgramRepository $programRepository
     * @param BlockRepository $blockRepository
     * @param PdfExportService $pdfExportService
     * @param ExcelExportService $excelExportService
     * @param MailService $mailService
     * @param Session $session
     * @param SubeventRepository $subeventRepository
     * @param ApplicationRepository $applicationRepository
     */
    public function __construct(Translator $translator, UserRepository $userRepository,
                                SettingsRepository $settingsRepository, CustomInputRepository $customInputRepository,
                                RoleRepository $roleRepository, ProgramRepository $programRepository,
                                BlockRepository $blockRepository, PdfExportService $pdfExportService,
                                ExcelExportService $excelExportService, MailService $mailService, Session $session,
                                SubeventRepository $subeventRepository, ApplicationRepository $applicationRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->customInputRepository = $customInputRepository;
        $this->roleRepository = $roleRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository = $blockRepository;
        $this->pdfExportService = $pdfExportService;
        $this->excelExportService = $excelExportService;
        $this->mailService = $mailService;
        $this->subeventRepository = $subeventRepository;
        $this->applicationRepository = $applicationRepository;

        $this->session = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/users_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentUsersGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->userRepository->createQueryBuilder('u'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();


        $grid->addGroupAction('admin.users.users_group_action_approve')
            ->onSelect[] = [$this, 'groupApprove'];

        $grid->addGroupMultiSelectAction('admin.users.users_group_action_change_roles',
            $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED]))
            ->onSelect[] = [$this, 'groupChangeRoles'];

        $grid->addGroupAction('admin.users.users_group_action_mark_attended')
            ->onSelect[] = [$this, 'groupMarkAttended'];

        $grid->addGroupAction('admin.users.users_group_action_mark_paid_today', $this->preparePaymentMethodOptionsWithoutEmpty())
            ->onSelect[] = [$this, 'groupMarkPaidToday'];

        $grid->addGroupAction('admin.users.users_group_action_generate_payment_proofs')
            ->onSelect[] = [$this, 'groupGeneratePaymentProofs'];

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

        $grid->addColumnText('roles', 'admin.users.users_roles', 'roles')
            ->setRenderer(function ($row) {
                $roles = [];
                foreach ($row->getRoles() as $role) {
                    $roles[] = $role->getName();
                }
                return implode(", ", $roles);
            })
            ->setFilterMultiSelect($this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED]))
            ->setCondition(function ($qb, $values) {
                $qb->join('u.roles', 'r')->where('r.id IN (:ids)')->setParameter(':ids', $values);
            });

        $grid->addColumnText('subevents', 'admin.users.users_subevents', 'subevents')
            ->setRenderer(function ($row) {
                $subevents = [];
                foreach ($row->getSubevents() as $subevent) {
                    $subevents[] = $subevent->getName();
                }
                return implode(", ", $subevents);
            })
            ->setFilterMultiSelect($this->subeventRepository->getExplicitOptions())
            ->setCondition(function ($qb, $values) {
                $qb
                    ->join('u.applications', 'a')
                    ->join('a.subevents', 's')
                    ->where('s.id IN (:ids)')
                    ->setParameter(':ids', $values);
            });

        $columnApproved = $grid->addColumnStatus('approved', 'admin.users.users_approved');
        $columnApproved
            ->addOption(FALSE, 'admin.users.users_approved_unapproved')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(TRUE, 'admin.users.users_approved_approved')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeApproved'];
        $columnApproved
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_approved_unapproved',
                '1' => 'admin.users.users_approved_approved'
            ])
            ->setTranslateOptions();

        $grid->addColumnText('unit', 'admin.users.users_membership')
            ->setRendererOnCondition(function ($row) {
                return Html::el('span')
                    ->style('color: red')
                    ->setText($row->isMember() ?
                        $this->translator->translate('admin.users.users_membership_no') :
                        (
                        $row->isExternal() ?
                            $this->translator->translate('admin.users.users_membership_external') :
                            $this->translator->translate('admin.users.users_membership_not_connected')
                        )
                    );
            }, function ($row) {
                return $row->getUnit() === NULL;
            }
            )
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('age', 'admin.users.users_age')
            ->setSortable()
            ->setSortableCallback(function ($qb, $sort) {
                $sort = $sort['age'] == 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.birthdate', $sort);
            });

        $grid->addColumnText('city', 'admin.users.users_city')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('fee', 'admin.users.users_fee');

        $grid->addColumnNumber('feeRemaining', 'admin.users.users_fee_remaining');

        $grid->addColumnText('variableSymbol', 'admin.users.users_variable_symbol')
            ->setRenderer(function ($row) {
                $variableSymbols = [];
                foreach ($row->getApplications() as $application) {
                    $variableSymbols[] = $application->getVariableSymbol();
                }
                return implode(", ", $variableSymbols);
            })
            ->setFilterText()
            ->setCondition(function ($qb, $value) {
                $qb
                    ->join('u.applications', 'a')
                    ->where('a.variableSymbol LIKE :variableSymbol')
                    ->setParameter(':variableSymbol', $value . '%');
            });

        $grid->addColumnText('paymentMethod', 'admin.users.users_payment_method')
            ->setRenderer(function ($row) {
                $paymentMethod = NULL;

                foreach ($row->getApplications() as $application) {
                    $currentPaymentMethod = $application->getPaymentMethod();
                    if ($currentPaymentMethod) {
                        if ($paymentMethod === NULL) {
                            $paymentMethod = $currentPaymentMethod;
                            continue;
                        }
                        if ($paymentMethod != $currentPaymentMethod) {
                            return $this->translator->translate('common.payment.mixed');
                        }
                    }
                }

                if ($paymentMethod)
                    return $this->translator->translate('common.payment.' . $paymentMethod);

                return NULL;
            });

        $grid->addColumnDateTime('lastPaymentDate', 'admin.users.users_last_payment_date');

//        $grid->addColumnDateTime('incomeProofPrintedDate', 'admin.users.users_income_proof_printed_date')
//            ->setSortable();

        $grid->addColumnDateTime('firstApplicationDate', 'admin.users.users_first_application_date')
            ->setFormat('j. n. Y H:i');

        $columnAttended = $grid->addColumnStatus('attended', 'admin.users.users_attended');
        $columnAttended
            ->addOption(FALSE, 'admin.users.users_attended_no')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(TRUE, 'admin.users.users_attended_yes')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeAttended'];
        $columnAttended
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_attended_no',
                '1' => 'admin.users.users_attended_yes'
            ])
            ->setTranslateOptions();

        $grid->addColumnText('unregisteredMandatoryBlocks', 'admin.users.users_not_registered_mandatory_blocks')
            ->setRenderer(function ($row) {
                if (!$row->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS) || !$row->isApproved())
                    return NULL;

                $unregisteredMandatoryBlocksNames = $this->blockRepository->findUserMandatoryNotRegisteredNames($row);
                $unregisteredMandatoryBlocksNamesText = implode(', ', $unregisteredMandatoryBlocksNames);
                return Html::el('span')
                    ->setAttribute('data-toggle', 'tooltip')
                    ->setAttribute('title', $unregisteredMandatoryBlocksNamesText)
                    ->setText(count($unregisteredMandatoryBlocksNames));
            });

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $grid->addColumnText('customInput' . $customInput->getId(), $this->truncate($customInput->getName(), 20))
                ->setRenderer(function ($row) use ($customInput) {
                    $customInputValue = $row->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        if ($customInputValue->getInput()->getType() == CustomInput::TEXT)
                            return $this->truncate($customInputValue->getValue(), 20);
                        else {
                            return $customInputValue->getValue() ?
                                $this->translator->translate('admin.common.yes') :
                                $this->translator->translate('admin.common.no');
                        }
                    }
                    return NULL;
                });
        }


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
                'data-content' => $this->translator->translate('admin.users.users_delete_confirm')
            ]);
        $grid->allowRowsAction('delete', function ($item) {
            return $item->isExternal();
        });

        $grid->setColumnsSummary(['fee']);
    }

    /**
     * Zpracuje odstranění uživatele.
     * @param $id
     */
    public function handleDelete($id)
    {
        $user = $this->userRepository->findById($id);

        $this->userRepository->remove($user);

        $this->getPresenter()->flashMessage('admin.users.users_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Změní stav uživatele.
     * @param $id
     * @param $approved
     */
    public function changeApproved($id, $approved)
    {
        $user = $this->userRepository->findById($id);

        $over = FALSE;
        if ($approved && !$user->isApproved()) {
            foreach ($user->getRoles() as $role) {
                $count = $this->roleRepository->countUnoccupiedInRole($role);
                if ($count !== NULL && $count < 1) {
                    $over = TRUE;
                    break;
                }
            }
        }

        $p = $this->getPresenter();

        if ($over) {
            $p->flashMessage('admin.users.users_change_approved_error', 'danger');
        } else {
            $user->setApproved($approved);
            $this->userRepository->save($user);

            $p->flashMessage('admin.users.users_changed_approved', 'success');
        }

        $this->redirect('this');
    }

    /**
     * Změní účast uživatele na semináři.
     * @param $id
     * @param $attended
     */
    public function changeAttended($id, $attended)
    {
        $user = $this->userRepository->findById($id);
        $user->setAttended($attended);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Hromadně schválí uživatele.
     * @param array $ids
     */
    public function groupApprove(array $ids)
    {
        $users = $this->userRepository->findUsersByIds($ids);
        $rolesWithLimitedCapacity = $this->roleRepository->findAllWithLimitedCapacity();
        $unoccupiedCounts = $this->roleRepository->countUnoccupiedInRoles($rolesWithLimitedCapacity);

        foreach ($users as $user) {
            if (!$user->isApproved()) {
                foreach ($user->getRoles() as $role) {
                    if ($role->hasLimitedCapacity())
                        $unoccupiedCounts[$role->getId()]--;
                }
            }
        }

        $over = FALSE;
        foreach ($unoccupiedCounts as $count) {
            if ($count < 0) {
                $over = TRUE;
                break;
            }
        }

        $p = $this->getPresenter();

        if ($over) {
            $p->flashMessage('admin.users.users_group_action_approve_error', 'danger');
        } else {
            foreach ($users as $user) {
                $user->setApproved(TRUE);
                $this->userRepository->save($user);
            }

            $p->flashMessage('admin.users.users_group_action_approved', 'success');
        }

        $this->redirect('this');
    }

    /**
     * Hromadně nastaví role.
     * @param array $ids
     * @param $value
     */
    public function groupChangeRoles(array $ids, $value)
    {
        $users = $this->userRepository->findUsersByIds($ids);
        $selectedRoles = $this->roleRepository->findRolesByIds($value);

        $p = $this->getPresenter();

        $error = FALSE;

        //neni vybrana zadna role
        if ($selectedRoles->isEmpty()) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_empty', 'danger');
            $error = TRUE;
        }

        //pokud je vybrana role neregistrovany, nesmi byt zadna vybrana jina role
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
        if ($selectedRoles->contains($nonregisteredRole) && $selectedRoles->count() > 1) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_nonregistered', 'danger');
            $error = TRUE;
        }

        //v rolich musi byt dostatek volnych mist
        $unoccupiedCounts = $this->roleRepository->countUnoccupiedInRoles($selectedRoles);
        foreach ($selectedRoles as $role) {
            if ($role->hasLimitedCapacity()) {
                foreach ($users as $user) {
                    if ($user->isApproved() && !$user->isInRole($role))
                        $unoccupiedCounts[$role->getId()]--;
                }
            }
        }
        foreach ($unoccupiedCounts as $count) {
            if ($count < 0) {
                $p->flashMessage('admin.users.users_group_action_change_roles_error_capacity', 'danger');
                $error = TRUE;
                break;
            }
        }

        if (!$error) {
            foreach ($users as $user) {
                $user->setRoles($selectedRoles);
                $this->userRepository->save($user);
            }

            $this->programRepository->updateUsersPrograms($users->toArray());
            $this->userRepository->getEntityManager()->flush();

            $p->flashMessage('admin.users.users_group_action_changed_roles', 'success');
        }

        $this->redirect('this');
    }

    /**
     * Hromadně označí uživatele jako zúčastněné.
     * @param array $ids
     */
    public function groupMarkAttended(array $ids)
    {
        $this->userRepository->setAttended($ids);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_group_action_marked_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Hromadně označí uživatele jako zaplacené dnes.
     * @param array $ids
     * @param $value
     */
    public function groupMarkPaidToday(array $ids, $value)
    {
        //TODO
        foreach ($ids as $id) {
            $user = $this->userRepository->findById($id);
            if ($user->isPaying()) {
                $user->setPaymentMethod($value);
                $user->setPaymentDate(new \DateTime());
                $this->userRepository->save($user);

                $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$user]), '', Template::PAYMENT_CONFIRMED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
                ]);
            }
        }

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_group_action_marked_paid_today', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Hromadně vygeneruje potvrzení o zaplacení.
     * @param array $ids
     */
    public function groupGeneratePaymentProofs(array $ids)
    {
        //TODO
        $this->sessionSection->userIds = $ids;
        $this->redirect('generatepaymentproofs'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Hromadně vyexportuje seznam uživatelů s rolemi.
     * @param array $ids
     */
    public function groupExportRoles(array $ids)
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportroles'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export seznamu uživatelů s rolemi.
     */
    public function handleExportRoles()
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);
        $roles = $this->roleRepository->findAll();

        $response = $this->excelExportService->exportUsersRoles($users, $roles, "role-uzivatelu.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje harmonogramy uživatelů.
     * @param array $ids
     */
    public function groupExportSchedules(array $ids)
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportschedules'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export harmonogramů uživatelů.
     */
    public function handleExportSchedules()
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSchedules($users, "harmonogramy-uzivatelu.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Vygeneruje doklady o zaplacení.
     */
    public function handleGeneratePaymentProofs()
    {
        //TODO
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);
        $usersToGenerate = [];

        foreach ($users as $user) {
            if ($user->getPaymentDate()) {
                if (!$user->getIncomeProofPrintedDate()) {
                    $user->setIncomeProofPrintedDate(new \DateTime());
                    $this->userRepository->save($user);
                }
                $usersToGenerate[] = $user;
            }
        }

        $this->pdfExportService->generatePaymentProofs($usersToGenerate, "doklady.pdf");
    }

    /**
     * Vrátí platební metody jako možnosti pro select. Bez prázdné možnosti.
     * @return array
     */
    private function preparePaymentMethodOptionsWithoutEmpty()
    {
        $options = [];
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }

    /**
     * Zkrátí $text na $length znaků a doplní '...'.
     * @param $text
     * @param $length
     * @return bool|string
     */
    private function truncate($text, $length)
    {
        if (strlen($text) > $length) {
            $text = $text . " ";
            $text = substr($text, 0, $length);
            $text = substr($text, 0, strrpos($text, ' '));
            $text = $text . "...";
        }
        return $text;
    }
}
