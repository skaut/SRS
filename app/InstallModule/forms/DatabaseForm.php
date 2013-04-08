<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form\Install;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Nastaveni pristupu k databazi
 */
class DatabaseForm extends UI\Form
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addText('host', 'Host:')
            ->addRule(Form::FILLED, 'Zadejte Host')->setDefaultValue('localhost');
        $this->addText('dbname', 'Databáze:')
            ->addRule(Form::FILLED, 'Zadejte Databázi');
        $this->addText('user', 'Uživatel:')
            ->addRule(Form::FILLED, 'Zadejte Uživatele');
        $this->addPassword('password', 'Heslo:')
            ->addRule(Form::FILLED, 'Zadejte Heslo:');

        $this->addSubmit('submit', 'Pokračovat')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        if (!$this->presenter->isDBConnection($values['dbname'], $values['host'], $values['user'], $values['password'])) {
            $this->presenter->flashMessage('Nepodařilo se připojit k databázi. Zadejte správné údaje', 'error');
        } else {
            $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR . '/config/config.neon'));
            $isDebug = $config['common']['parameters']['debug'];
            $environment = $isDebug == true ? 'development' : 'production';
            $values['installed'] = true;
            $values['schema_imported'] = false;
            $config["{$environment} < common"]['parameters']['database'] = $values;
            $config["{$environment} < common"]['parameters']['skautis']['app_id'] = null; // resetujeme nastaveni


            $configFile = \Nette\Utils\Neon::encode($config, \Nette\Utils\Neon::BLOCK);
            $result = \file_put_contents(APP_DIR . '/config/config.neon', $configFile);
            if ($result === false) {
                $this->presenter->flashMessage('Připojení k DB bylo úspěšné, ale nepodařilo se informace zapsat do souboru config.neon. Zkontrolujte práva k souboru', 'error');

            } else {
                $this->presenter->flashMessage('Spojení s databází úspěšně navázáno', 'success');
                $this->presenter->redirect(':Install:install:schema');
            }
        }


    }

}