<?php

namespace App\AdminModule\Components;

use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;

class UsersGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var CustomInputRepository
     */
    private $customInputRepository;

    public function __construct(Translator $translator, UserRepository $userRepository, SettingsRepository $settingsRepository, CustomInputRepository $customInputRepository)
    {
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->customInputRepository = $customInputRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/users_grid.latte');
    }

    public function createComponentUsersGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->userRepository->createQueryBuilder('user'));
        $grid->setColumnsHideable();

        $grid->addGroupAction('Delete examples')->onSelect[] = [$this, 'deleteExamples']; //TODO akce

        $grid->addColumnText('displayName', 'Jméno')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('username', 'Uživatelské jméno')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('rolesText', 'Role'); //TODO filtr

        $grid->addColumnText('approved', 'Schválený')
            ->setReplacement(['0' => 'ne', '1' => 'ano'])
            ->setSortable()
            ->setFilterSelect(['' => 'vše', '0' => 'ne', '1' => 'ano']);

        $grid->addColumnText('unit', 'Členství')
            ->setRendererOnCondition(function ($row) {
                return Html::el('span')->style('color: red')->setText($row->isMember() ? 'nečlen' : 'nepropojený účet');
            }, function ($row) {
                return $row->getUnit() === null;
            })
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('age', 'Věk')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('city', 'Město')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('fee', 'Cena'); //TODO sort

        $grid->addColumnText('paymentMethod', 'Platební metoda'); //TODO

        $variableSymbolCode = $this->settingsRepository->getValue('variable_symbol_code');
        $grid->addColumnText('variableSymbol', 'Variabilní symbol')
            ->setRenderer(function ($row) use($variableSymbolCode) {
                return $row->getVariableSymbolWithCode($variableSymbolCode);
            })
            ->setSortable();

        $grid->addColumnDateTime('paymentDate', 'Zaplaceno'); //TODO

        $grid->addColumnDateTime('incomeProofPrintedDate', 'Doklad vytištěn dne')
            ->setSortable();

        $grid->addColumnDateTime('firstLogin', 'Registrace')
            ->setSortable();

        $grid->addColumnText('attended', 'Přítomen')
            ->setReplacement(['0' => 'ne', '1' => 'ano'])
            ->setSortable()
            ->setFilterSelect(['' => 'vše', '0' => 'ne', '1' => 'ano']);

//        $customBooleansCount = $this->settingsRepository->getValue('custom_booleans_count');
//
//        for ($i = 0; $i < $customBooleansCount; $i++) {
//            //TODO
//        }
//
//        $customTextsCount = $this->settingsRepository->getValue('custom_texts_count');
//
//        for ($i = 0; $i < $customTextsCount; $i++) {
//            //TODO
//        }

        //$grid->addAction('edit', 'Edit'); //TODO akce

        $grid->setColumnsSummary(['fee']);
    }
}