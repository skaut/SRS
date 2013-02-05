<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */


namespace SRS\Form;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;


class SettingsForm extends UI\Form
{

    protected $dbsettings;

    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        parent::__construct($parent, $name);
        $this->dbsettings = $dbsettings;


        $basicBlockDurationChoices = array('15' => '15 minut', '30' => '30 minut', '45' => '45 minut', '60' => '60 minut', '75' => '75 minut', '90' => '90 minut', '105' => '105 minut',  '120' => '120 minut');

        $this->addText('seminar_name', 'Jméno semináře:')->setDefaultValue($this->dbsettings->get('seminar_name'))
            ->addRule(Form::FILLED, 'Zadejte Jméno semináře');
        $this->addText('seminar_from_date', 'Začátek semináře:')->setDefaultValue($this->dbsettings->get('seminar_from_date'))
            ->addRule(Form::FILLED, 'Zadejte začátek semináře');
        $this->addText('seminar_to_date', 'Konec semináře:')->setDefaultValue($this->dbsettings->get('seminar_to_date'))
            ->addRule(Form::FILLED, 'Zadejte konec semináře');
        $this->addSelect('basic_block_duration','Základní délka trvání jednoho progrmaového bloku semináře:' )->setItems($basicBlockDurationChoices)->setDefaultValue($this->dbsettings->get('basic_block_duration'));

        $this->addSubmit('submit','Uložit')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        foreach ($values as $key => $value) {
            $this->dbsettings->set($key, $value);
        }
        $this->presenter->flashMessage('Konfigurace uložena', 'success');

    }

}