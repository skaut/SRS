<?php

namespace App\AdminModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Program\ProgramRepository;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RolesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var ProgramService */
    private $programService;


    /**
     * RolesGridControl constructor.
     * @param Translator $translator
     * @param RoleRepository $roleRepository
     * @param UserRepository $userRepository
     * @param ProgramRepository $programRepository
     * @param ProgramService $programService
     */
    public function __construct(Translator $translator, RoleRepository $roleRepository, UserRepository $userRepository,
                                ProgramRepository $programRepository, ProgramService $programService)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->roleRepository = $roleRepository;
        $this->userRepository = $userRepository;
        $this->programRepository = $programRepository;
        $this->programService = $programService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/roles_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridColumnStatusException
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentRolesGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->roleRepository->createQueryBuilder('r'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.acl.roles_name');

        $grid->addColumnText('system', 'admin.acl.roles_system')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes')
            ]);

        $grid->addColumnStatus('registerable', 'admin.acl.roles_registerable')
            ->addOption(FALSE, 'admin.acl.roles_registerable_nonregisterable')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(TRUE, 'admin.acl.roles_registerable_registerable')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeRegisterable'];

        $grid->addColumnDateTime('registerableFrom', 'admin.acl.roles_registerable_from')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnDateTime('registerableTo', 'admin.acl.roles_registerable_to')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnText('occupancy', 'admin.acl.roles_occupancy')
            ->setRenderer(
                function ($row) {
                    $capacity = $row->getCapacity();
                    if ($capacity === NULL)
                        return $this->roleRepository->countApprovedUsersInRole($row);
                    return $this->roleRepository->countApprovedUsersInRole($row) . "/" . $capacity;
                }
            );

        $grid->addColumnText('fee', 'admin.acl.roles_fee')
            ->setRendererOnCondition(function ($row) {
                return $this->translator->translate('admin.acl.roles_fee_from_subevents');
            }, function ($row) {
                return $row->getFee() === NULL;
            });


        $grid->addToolbarButton('Acl:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('test', 'admin.acl.roles_test', 'Acl:test')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('edit', 'admin.common.edit', 'Acl:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.acl.roles_delete_confirm')
            ]);
        $grid->allowRowsAction('delete', function ($item) {
            return !$item->isSystem();
        });
    }

    /**
     * Zpracuje odstranění role.
     * @param $id
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function handleDelete($id)
    {
        $role = $this->roleRepository->findById($id);

        $usersInRole = $this->userRepository->findAllInRole($role);

        $this->roleRepository->getEntityManager()->transactional(function ($em) use ($role, $usersInRole) {
            $this->roleRepository->remove($role);

            foreach ($usersInRole as $user) {
                if ($user->getRoles()->isEmpty()) {
                    $user->addRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));
                    $user->setApproved(TRUE);
                    $this->userRepository->save($user);
                }
            }

            $this->programService->updateUsersPrograms($this->userRepository->findAll());
        });

        $this->getPresenter()->flashMessage('admin.acl.roles_deleted', 'success');
        $this->redirect('this');
    }

    /**
     * Změní registrovatelnost role.
     * @param $id
     * @param $registerable
     * @throws \Nette\Application\AbortException
     */
    public function changeRegisterable($id, $registerable)
    {
        $role = $this->roleRepository->findById($id);

        $role->setRegisterable($registerable);
        $this->roleRepository->save($role);

        $p = $this->getPresenter();
        $p->flashMessage('admin.acl.roles_changed_registerable', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['rolesGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
