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
class SettingsFormPrint extends \SRS\Form\Configuration\SettingsForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        parent::__construct($parent, $name, $dbsettings);

        $this->addTextArea('company', 'Firma')->setDefaultValue($this->dbsettings->get('company'));
        $this->addText('ico', 'IČO')->setDefaultValue($this->dbsettings->get('ico'));
        $this->addText('accountant', 'Pokladník')->setDefaultValue($this->dbsettings->get('accountant'));
        $this->addText('print_location', 'Lokalita')->setDefaultValue($this->dbsettings->get('print_location'));
        $this->addSubmit('submit_print', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }
}