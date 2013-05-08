<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form\Configuration;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * formular pro konfiguraci
 */
class SettingsForm extends UI\Form
{

    protected $dbsettings;

    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings, $configParameters)
    {
        parent::__construct($parent, $name);
        $this->dbsettings = $dbsettings;


        $basicBlockDurationChoices = array('15' => '15 minut', '30' => '30 minut', '45' => '45 minut', '60' => '60 minut', '75' => '75 minut', '90' => '90 minut', '105' => '105 minut', '120' => '120 minut');
        $this->addGroup('Seminář');
        $this->addText('seminar_name', 'Jméno semináře:')->setDefaultValue($this->dbsettings->get('seminar_name'))
            ->addRule(Form::FILLED, 'Zadejte Jméno semináře');
        $this->addText('seminar_from_date', 'Začátek semináře:')->setDefaultValue($this->dbsettings->get('seminar_from_date'))
            ->addRule(FORM::PATTERN, 'Datum Začátku semináře není ve správném tvaru', \SRS\Helpers::DATE_PATTERN)
            ->addRule(Form::FILLED, 'Zadejte začátek semináře')->getControlPrototype()->class('datepicker');
        $this->addText('seminar_to_date', 'Konec semináře:')->setDefaultValue($this->dbsettings->get('seminar_to_date'))
            ->addRule(FORM::PATTERN, 'Datum konce semináře není ve správném tvaru', \SRS\Helpers::DATE_PATTERN)
            ->addRule(Form::FILLED, 'Zadejte konec semináře')->getControlPrototype()->class('datepicker');
        $this->addText('seminar_email', 'Email pro mailing:')->setDefaultValue($this->dbsettings->get('seminar_email'))
            ->addRule(Form::FILLED, 'Zadejte Email pro mailing')
            ->addRule(FORM::EMAIL, 'Email není ve správném tvaru');

        $this->addSubmit('submit_seminar', 'Uložit')->getControlPrototype()->class('btn');


        $this->addGroup('Program');
        $this->addSelect('basic_block_duration', 'Základní délka trvání program. bloku semináře:')->setItems($basicBlockDurationChoices)->setDefaultValue($this->dbsettings->get('basic_block_duration'));
        $this->addCheckbox('is_allowed_add_block', 'Je povoleno vytvářet programové bloky?')->setDefaultValue($this->dbsettings->get('is_allowed_add_block'));
        $this->addCheckbox('is_allowed_modify_schedule', 'Je povoleno upravovat harmonogram semináře?')->setDefaultValue($this->dbsettings->get('is_allowed_modify_schedule'));
        $this->addCheckbox('is_allowed_log_in_programs', 'Je povoleno přihlašovat se na programové bloky?')->setDefaultValue($this->dbsettings->get('is_allowed_log_in_programs'));
        $this->addSubmit('submit_program', 'Uložit')->getControlPrototype()->class('btn');

        $this->addGroup('Tisk dokladů');
        $this->addTextArea('company', 'Firma:')->setDefaultValue($this->dbsettings->get('company'));
        $this->addText('ico', 'IČO:')->setDefaultValue($this->dbsettings->get('ico'));
        $this->addText('accountant', 'Pokladník:')->setDefaultValue($this->dbsettings->get('accountant'));
        $this->addText('account_number', 'Číslo účtu:')->setDefaultValue($this->dbsettings->get('account_number'));
        $this->addText('print_location', 'Lokalita:')->setDefaultValue($this->dbsettings->get('print_location'));
        $this->addSubmit('submit_print', 'Uložit')->getControlPrototype()->class('btn');


        $this->addGroup('Vlastní pole pro přihlášku');
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

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');


    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        if (\DateTime::createFromFormat("Y-m-d", $values['seminar_to_date']) < \DateTime::createFromFormat("Y-m-d", $values['seminar_from_date'])) {
            $this->presenter->flashMessage('Datum konce semináře nemůže být menší než začátku', 'error');
        } else {
            foreach ($values as $key => $value) {
                $this->dbsettings->set($key, $value);
            }
            $this->presenter->flashMessage('Konfigurace uložena', 'success');
            $this->presenter->redirect('this');
        }

    }

}