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
 * formular pro konfiguraci
 */
abstract class SettingsForm extends UI\Form
{
    protected $dbsettings;

    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        parent::__construct($parent, $name);
        $this->dbsettings = $dbsettings;
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        foreach ($values as $key => $value) {
            $this->dbsettings->set($key, $value);
        }
        $this->presenter->flashMessage('Konfigurace uložena', 'success');
        $this->presenter->redirect('this');
    }
}