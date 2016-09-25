<?php
/**
 * Date: 19.2.13
 * Time: 9:42
 * Author: Michal Májský
 */

namespace SRS\Form\Evidence;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro vyber sloupcu ke zobrazeni v evidenci ucastniku
 */
class ColumnForm extends Form
{

    protected $container;

    public function __construct(IContainer $parent = NULL, $name = NULL, $columns, $container)
    {
        $this->container = $container;
        parent::__construct($parent, $name);

        $session = $container->session;
        $evidenceColumns = $session->getSection('evidenceColumns');
        $visibilities = $evidenceColumns->visibility;

        foreach ($columns as $column) {
            $this->addCheckbox($column['name'], $column['label'])->setDefaultValue($visibilities[$column['name']]);

        }
        $this->addSubmit('submit', 'OK');
        //$this->onSuccess[] = callback($this->presenter, 'columnFormSubmitted');
    }


}
