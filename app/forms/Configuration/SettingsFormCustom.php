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
 * formular pro konfiguraci programu
 */
class SettingsFormCustom extends \SRS\Form\Configuration\SettingsForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings, $configParameters)
    {
        parent::__construct($parent, $name, $dbsettings);

        $CUSTOM_BOOLEAN_COUNT = $configParameters['user_custom_boolean_count'];
        for ($i = 0; $i < $CUSTOM_BOOLEAN_COUNT; $i++) {
            $column = 'user_custom_boolean_' . $i;
            $this->addText($column, 'Vlastní checkbox pro přihlášku č.' . $i)->setDefaultValue($this->dbsettings->get($column));
        }

        $CUSTOM_TEXT_COUNT = $configParameters['user_custom_text_count'];
        for ($i = 0; $i < $CUSTOM_TEXT_COUNT; $i++) {
            $column = 'user_custom_text_' . $i;
            $this->addText($column, 'Vlastní textové pole pro přihlášku č.' . $i)->setDefaultValue($this->dbsettings->get($column));
        }

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }
}