<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form\Configuration;

use Nette\Application\UI,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * formular pro konfiguraci platby
 */
class SettingsFormPayment extends \SRS\Form\Configuration\SettingsForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        parent::__construct($parent, $name, $dbsettings);

        $this->addText('account_number', 'Číslo účtu')->setDefaultValue($this->dbsettings->get('account_number'))
            ->addRule(Form::FILLED, 'Zadejte číslo účtu');

        $this->addText('variable_symbol_code', 'Předvolba variabilního symbolu (neovlivní již vygenerované)', 2)->setDefaultValue($this->dbsettings->get('variable_symbol_code'))
            ->addRule(Form::FILLED, 'Zadejte předvolbu variabilního symbolu')
            ->addRule(Form::INTEGER, 'Zadejte 2 číslice')
            ->addRule(Form::COUNT, 'Zadejte 2 číslice', 2);

        $this->addSubmit('submit_payment', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }
}