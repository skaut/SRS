<?php

namespace App\AdminModule\Components;

use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class UsersGridControl extends Control
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    public function render()
    {
        $this->template->render(__DIR__ . '/templates/users_grid.latte');
    }


    public function createComponentGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setDataSource($this->userRepository->findAll());
        $grid->addColumnText('displayName', 'Jméno')
            ->setSortable(true);
        $grid->addColumnText('username', 'Uživatelské jméno');
        $grid->addColumnText('roles', 'Role')
            ->setRenderer(function ($item) {
                $rolesText = "";
                foreach ($item->getRoles() as $role) {
                    $rolesText .= $role->getName();
                }
                return $rolesText;
            })
            ->setSortable(true);

        $grid->addColumnText('approved', 'Schválený')
            ->setReplacement(['0' => 'ne', '1' => 'ano']);

    }
}