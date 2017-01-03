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
        $grid->addColumnText('username', 'Name');
    }
}