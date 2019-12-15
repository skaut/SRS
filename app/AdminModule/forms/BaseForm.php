<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use Nette\Application\UI\Form;
use Nextras\Forms\Controls\DatePicker;
use Nextras\Forms\Controls\DateTimePicker;
use VojtechDobes\NetteForms\GpsPicker;


/**
 * BaseForm pro AdminModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @method DatePicker addDatePicker($name, $label = null)
 * @method DateTimePicker addDateTimePicker($name, $label = null)
 * @method GpsPicker addGpsPicker($name, $label = null)
 */
class BaseForm extends Form
{
}