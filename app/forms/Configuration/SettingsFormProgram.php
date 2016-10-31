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
class SettingsFormProgram extends \SRS\Form\Configuration\SettingsForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        parent::__construct($parent, $name, $dbsettings);

        $basicBlockDurationChoices = array('5' => '5 minut', '10' => '10 minut', '15' => '15 minut', '30' => '30 minut', '45' => '45 minut', '60' => '60 minut', '75' => '75 minut', '90' => '90 minut', '105' => '105 minut', '120' => '120 minut');


        $this->addSelect('basic_block_duration', 'Základní délka trvání programového bloku semináře')
            ->setItems($basicBlockDurationChoices)->setDefaultValue($this->dbsettings->get('basic_block_duration'));

        $this->addCheckbox('is_allowed_add_block', 'Je povoleno vytvářet programové bloky?')
            ->setDefaultValue($this->dbsettings->get('is_allowed_add_block'));

        $this->addCheckbox('is_allowed_modify_schedule', 'Je povoleno upravovat harmonogram semináře?')
            ->setDefaultValue($this->dbsettings->get('is_allowed_modify_schedule'));

        $this->addCheckbox('is_allowed_log_in_programs', 'Je povoleno přihlašovat se na programové bloky?')
            ->setDefaultValue($this->dbsettings->get('is_allowed_log_in_programs'));

        $this->addCheckbox('log_in_programs_after_payment', 'Povolit zápis programu až po uhrazení poplatku?')
            ->setDefaultValue($this->dbsettings->get('log_in_programs_after_payment'));

        $this->addText('log_in_programs_from', 'Přihlašování programů otevřeno od')->setDefaultValue($this->dbsettings->get('log_in_programs_from'))
            ->addRule(FORM::PATTERN, 'Datum a čas, od kdy je možné se přihlašovat na programy, není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN)
            ->addRule(Form::FILLED, 'Zadejte datum a čas, od kdy je možné se přihlašovat na programy')->getControlPrototype()->class('datetimepicker');

        $this->addText('log_in_programs_to', 'Přihlašování programů otevřeno do')->setDefaultValue($this->dbsettings->get('log_in_programs_to'))
            ->addRule(FORM::PATTERN, 'Datum a čas, do kdy je možné se přihlašovat na programy, není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN)
            ->addRule(Form::FILLED, 'Zadejte datum a čas, do kdy je možné se přihlašovat na programy')->getControlPrototype()->class('datetimepicker');

        $this->addSubmit('submit_program', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        if (\DateTime::createFromFormat("d.m.Y H:i", $values['log_in_programs_to']) < \DateTime::createFromFormat("d.m.Y H:i", $values['log_in_programs_from'])) {
            $this->presenter->flashMessage('Datum a čas konce přihlašování na programové bloky musí být větší než začátku přihlašování', 'error');
        }
        else {
            foreach ($values as $key => $value) {
                $this->dbsettings->set($key, $value);
            }
            $this->presenter->flashMessage('Konfigurace uložena', 'success');
            $this->presenter->redirect('this');
        }

    }

}