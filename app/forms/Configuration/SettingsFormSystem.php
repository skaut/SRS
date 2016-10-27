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
 * formular pro konfiguraci tisku
 */
class SettingsFormSystem extends \SRS\Form\Configuration\SettingsForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        parent::__construct($parent, $name, $dbsettings);

        $this->addCheckbox('display_users_roles', 'Zobrazovat uživatelské role?')
            ->setDefaultValue($this->dbsettings->get('display_users_roles'));

        $this->addSubmit('submit_system', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }
}