<?php

namespace App\AdminModule\StructureModule\Components;

use App\Model\ACL\Role;
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


    /**
     * SubeventsGridControl constructor.
     * @param Translator $translator
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(Translator $translator, SubeventRepository $subeventRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->subeventRepository = $subeventRepository;
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
     */
    public function createComponentSubeventsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->subeventRepository->createQueryBuilder('s'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.structure.subevents_name');

        $grid->addColumnText('implicit', 'admin.structure.subevents_implicit')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes')
            ]);

        $grid->addColumnNumber('fee', 'admin.structure.subevents_fee');

        $grid->addColumnText('capacity', 'admin.structure.subevents_capacity')
            ->setRendererOnCondition(function ($row) {
                return $this->translator->translate('admin.structure.subevents_capacity_unlimited');
            }, function ($row) {
                return $row->getCapacity() === NULL;
            }
            );


        $grid->addToolbarButton('Subevents:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', 'admin.common.edit', 'Subevents:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.structure.subevents_delete_confirm')
            ]);
        $grid->allowRowsAction('delete', function ($item) {
            return !$item->isImplicit();
        });
    }

    /**
     * Zpracuje odstranění role.
     * @param $id
     */
    public function handleDelete($id)
    {
//        $role = $this->roleRepository->findById($id);
//
//        $usersInRole = $this->userRepository->findAllInRole($role);
//
//        $this->roleRepository->remove($role);
//
//        foreach ($usersInRole as $user) {
//            if ($user->getRoles()->isEmpty()) {
//                $user->addRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));
//                $this->userRepository->save($user);
//            }
//        }
//
//        $this->programRepository->updateUsersPrograms($this->userRepository->findAll());
//        $this->programRepository->getEntityManager()->flush();
//
//        $this->getPresenter()->flashMessage('admin.acl.roles_deleted', 'success');
//
//        $this->redirect('this');
    }
}
