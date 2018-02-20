<?php

namespace App\AdminModule\CMSModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Document\Tag;
use App\Model\CMS\Document\TagRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu štítků dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DocumentTagsGridControl extends Control
{
    /** @var Translator */
    private $translator;
	
	/** @var RoleRepository */
    private $roleRepository;
	
    /** @var TagRepository */
    private $tagRepository;


    /**
     * DocumentTagsGridControl constructor.
     * @param Translator $translator
     * @param TagRepository $tagRepository
     */
    public function __construct(Translator $translator, RoleRepository $roleRepository, TagRepository $tagRepository)
    {
        parent::__construct();

        $this->translator = $translator;
		$this->roleRepository = $roleRepository;
        $this->tagRepository = $tagRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/document_tags_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentDocumentTagsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->tagRepository->createQueryBuilder('t'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.cms.tags_name');
		
		$rolesOptions = $this->roleRepository->getRolesWithoutRolesOptions([]);
		//bdump($rolesOptions);
		$grid->addColumnText('userRoles', 'admin.tags.roles')
            ->setRenderer(function ($row) {
                $roles = [];
				//bdump($row->getRoles());
				if($row->getRoles()) {
					foreach ($row->getRoles() as $role) {
						$roles[] = $role->getName();
					}
				}
                return implode(", ", $roles);
            });
		
        $grid->addInlineAdd()->onControlAdd[] = function ($container) use ($rolesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.tags_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.tags_name_exists', $this->tagRepository->findAllNames());
			
			$container->addMultiSelect('roles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.tags_roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) use ($rolesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.tags_roles_empty');
			
			$container->addMultiSelect('userRoles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.tags_roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container['name']
                ->addRule(Form::IS_NOT_IN, 'admin.cms.tags_name_exists', $this->tagRepository->findOthersNames($item->getId()));

			if($item->getRoles()) {
				$container->setDefaults([
					'name' => $item->getName(),
					'userRoles' => $this->roleRepository->findRolesIds($item->getRoles())
				]);
			}
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];


        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.tags_delete_confirm')
            ]);
    }

    /**
     * Zpracuje přidání štítku dokumentu.
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function add($values)
    {
        $tag = new Tag();

        $tag->setName($values['name']);

        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu štítku dokumentu.
     * @param $id
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function edit($id, $values)
    {
		$tag = $this->tagRepository->findById($id);

		$userRoles = [];
		
		foreach ($values['userRoles'] as $userRole) {
			bdump($this->roleRepository->find($userRole));
			$userRoles[] = $this->roleRepository->find($userRole);
		}
		
        $tag->setName($values['name']);
		$tag->setRegisterableRoles($userRoles);
		
        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění štítku dokumentu.
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $tag = $this->tagRepository->findById($id);
        $this->tagRepository->remove($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_deleted', 'success');

        $this->redirect('this');
    }
}
