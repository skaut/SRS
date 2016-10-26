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
 * formular pro konfiguraci seminare
 */
class SettingsFormSeminar extends \SRS\Form\Configuration\SettingsForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        parent::__construct($parent, $name, $dbsettings);

        $this->addText('seminar_name', 'Jméno semináře')->setDefaultValue($this->dbsettings->get('seminar_name'))
            ->addRule(Form::FILLED, 'Zadejte Jméno semináře');

        $this->addText('seminar_from_date', 'Začátek semináře')->setDefaultValue($this->dbsettings->get('seminar_from_date'))
            ->addRule(Form::PATTERN, 'Datum začátku semináře není ve správném tvaru', \SRS\Helpers::DATE_PATTERN)
            ->addRule(Form::FILLED, 'Zadejte začátek semináře')->getControlPrototype()->class('datepicker');

        $this->addText('seminar_to_date', 'Konec semináře')->setDefaultValue($this->dbsettings->get('seminar_to_date'))
            ->addRule(Form::PATTERN, 'Datum konce semináře není ve správném tvaru', \SRS\Helpers::DATE_PATTERN)
            ->addRule(Form::FILLED, 'Zadejte konec semináře')->getControlPrototype()->class('datepicker');

        $this->addText('cancel_registration_to_date', 'Odhlašování povoleno do')->setDefaultValue($this->dbsettings->get('cancel_registration_to_date'))
            ->addRule(Form::PATTERN, 'Datum, do kdy je možné se ze semináře odhlásit, není ve správném tvaru', \SRS\Helpers::DATE_PATTERN)
            ->addRule(Form::FILLED, 'Zadejte datum, do kdy je možné se ze semináře odhlásit')->getControlPrototype()->class('datepicker');

        $this->addText('seminar_email', 'Email pro mailing')->setDefaultValue($this->dbsettings->get('seminar_email'))
            ->addRule(Form::FILLED, 'Zadejte Email pro mailing')
            ->addRule(Form::EMAIL, 'Email není ve správném tvaru');

        $this->addSubmit('submit_seminar', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        if (\DateTime::createFromFormat("d.m.Y", $values['seminar_to_date']) < \DateTime::createFromFormat("d.m.Y", $values['seminar_from_date'])) {
            $this->presenter->flashMessage('Datum konce semináře nemůže být menší než začátku', 'error');
        }
        else if (\DateTime::createFromFormat("d.m.Y", $values['seminar_from_date']) < \DateTime::createFromFormat("d.m.Y", $values['cancel_registration_to_date'])) {
            $this->presenter->flashMessage('Datum konce odhlašování nemůže být větší než začátku', 'error');
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