<?php

namespace App\AdminModule\Components;

use App\Model\Settings\SettingsRepository;
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

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    public function __construct(UserRepository $userRepository, SettingsRepository $settingsRepository)
    {
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
    }


    public function render()
    {
        $this->template->render(__DIR__ . '/templates/users_grid.latte');
    }


    public function createComponentGrid($name)
    {
        $settingsRepository = $this->settingsRepository;

        $grid = new DataGrid($this, $name);
        $grid->setDataSource($this->userRepository->findAll());

        $grid->addGroupAction('Delete examples')->onSelect[] = [$this, 'deleteExamples'];

        $grid->addColumnText('displayName', 'Jméno')
            ->setSortable()
            ->setSortableCallback(function($datasource, $sort) {
                $order = $sort['displayName'] == "ASC";
                usort($datasource, function($a, $b) use($order) {
                    $ret = strnatcmp($a->getDisplayName(), $b->getDisplayName());
                    return $order ? $ret : -$ret;
                });
                return $datasource;
            });

        $grid->addColumnText('username', 'Uživatelské jméno')
            ->setSortable()
            ->setSortableCallback(function($datasource, $sort) {
                $order = $sort['username'] == "ASC";
                usort($datasource, function($a, $b) use($order) {
                    $ret = strnatcmp($a->getUsername(), $b->getUsername());
                    return $order ? $ret : -$ret;
                });
                return $datasource;
            })
            ->setFilterText();

        $grid->addColumnText('rolesText', 'Role');

        $grid->addColumnText('approved', 'Schválený')
            ->setReplacement(['0' => 'ne', '1' => 'ano']);

        $grid->addColumnText('unit', 'Členství')
            ->setRendererOnCondition(function ($row) {
                return $row->isMember() ? "Nečlen" : "Nepropojený účet";
            }, function ($row) {
                return $row->getUnit() == null;
            });

        $grid->addColumnText('age', 'Věk');

        $grid->addColumnText('city', 'Město');

        $grid->addColumnText('fee', 'Cena');

        $grid->addColumnText('paymentMethod', 'Platební metoda');

        $grid->addColumnText('variableSymbol', 'Variabilní symbol')
            ->setRenderer(function ($row) use($settingsRepository) {
                return $row->getVariableSymbolWithCode($settingsRepository->getValue('variable_symbol_code'));
            });

        $grid->addColumnText('paymentDate', 'Zaplaceno');
    }
}