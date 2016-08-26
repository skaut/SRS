<?php
/**
 * Date: 30.10.12
 * Time: 21:08
 * Author: Michal Májský
 */
namespace SRS\Components;
use \NiftyGrid\Grid;
use \Doctrine\ORM\Query\Expr;

/**
 * Grid pro správu uživatelů a práv
 */
class EvidenceGrid extends Grid
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Nella\Doctrine\Repository
     */
    protected $dbsettings;

    /**
     * @var array
     */
    protected $columnsVisibility;


    public function __construct($em, $columnsVisibility)
    {
        parent::__construct();
        $this->em = $em;
        $this->dbsettings = $this->em->getRepository('\SRS\Model\Settings');
        $this->templatePath = __DIR__ . '/template.latte';
        $this->columnsVisibility = $columnsVisibility;
    }

    protected function configure($presenter)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->addSelect('u');
        $qb->addSelect('role');
        $qb->from('\SRS\Model\User', 'u');
        $qb->leftJoin('u.role', 'role'); //'WITH', 'role.standAlone=1');


        $roles = $this->em->getRepository('\SRS\Model\Acl\Role')->findAll();

        $rolesGrid = array();

        $numOfResults = 10;
        $today = new \DateTime('now');

        foreach ($roles as $role) {
            $rolesGrid[$role->id] = $role->name;
        }
        $source = new \SRS\SRSDoctrineDataSource($qb, 'id');
        $this->setDataSource($source);


        $self = $this;
        $visibility = $this->columnsVisibility;
        if ($this->columnsVisibility['displayName'])
            $this->addColumn('displayName', 'Jméno')->setTextFilter()->setAutocomplete($numOfResults);

        if ($this->columnsVisibility['role'])
            $this->addColumn('role', 'Role')
                ->setRenderer(function ($row) {
                return $row->role['name'];
            })
                ->setSelectFilter($rolesGrid);

        if ($this->columnsVisibility['birthdate'])
            $this->addColumn('birthdate', 'Věk')
                ->setRenderer(function ($row) use ($today) {
                $interval = $today->diff($row->birthdate);
                return $interval->y;
            });

        if ($this->columnsVisibility['city'])
            $this->addColumn('city', 'Město')
                ->setTextFilter()
                ->setAutocomplete($numOfResults);

        $paymentMethods = $presenter->context->parameters['payment_methods'];

        if ($this->columnsVisibility['paymentMethod'])
            $this->addColumn('paymentMethod', 'Platební metoda')
                ->setSelectFilter($paymentMethods)
                ->setSelectEditable($paymentMethods, 'nezadáno')
                ->setRenderer(function ($row) use ($paymentMethods) {
                if ($row->paymentMethod == null || $row->paymentMethod == '') return '';
                return $paymentMethods[$row->paymentMethod];
            });

        if ($this->columnsVisibility['paymentDate'])
            $this->addColumn('paymentDate', 'Zaplaceno dne')
                ->setDateEditable()
                ->setDateFilter()
                ->setRenderer(function ($row) {
                if ($row->paymentDate == null || $row->paymentDate == '') return '';
                return $row->paymentDate->format('d.m. Y');
            });

        if ($this->columnsVisibility['incomeProofPrinted'])
            $this->addColumn('incomeProofPrinted', 'Příjm. doklad vytištěn?')
                ->setBooleanEditable()
                ->setBooleanFilter()
                ->setRenderer(function ($row) {
                return \SRS\Helpers::renderBoolean($row->incomeProofPrinted);
            });


        if ($this->columnsVisibility['attended'])
            $this->addColumn('attended', 'Přítomen')
                ->setBooleanFilter()
                ->setBooleanEditable()
                ->setRenderer(function ($row) {
                return \SRS\Helpers::renderBoolean($row->attended);
            });

        $CUSTOM_BOOLEAN_COUNT = $presenter->context->parameters['user_custom_boolean_count'];
        for ($i = 0; $i < $CUSTOM_BOOLEAN_COUNT; $i++) {
            $column = 'user_custom_boolean_' . $i;
            $dbvalue = $this->dbsettings->get($column);
            $propertyName = 'customBoolean' . $i;

            if ($dbvalue != '' && $this->columnsVisibility[$propertyName]) {
                $this->addColumn($propertyName, $this->dbsettings->get($column))
                    ->setBooleanFilter()
                    ->setBooleanEditable()
                    ->setRenderer(function ($row) use ($i) {
                    return \SRS\Helpers::renderBoolean($row->{"customBoolean$i"});
                });
            }
        }

        $CUSTOM_TEXT_COUNT = $presenter->context->parameters['user_custom_text_count'];
        for ($i = 0; $i < $CUSTOM_TEXT_COUNT; $i++) {
            $column = 'user_custom_text_' . $i;
            $dbvalue = $this->dbsettings->get($column);
            $propertyName = 'customText' . $i;

            if ($dbvalue != '' && $this->columnsVisibility[$propertyName]) {
                $this->addColumn($propertyName, $this->dbsettings->get($column))
                    ->setTextFilter()
                    ->setTextEditable();

            }
        }

        $this->addButton(Grid::ROW_FORM, "Řádková editace")
            ->setClass("fast-edit");

        $this->addButton("detail", "Detail")
            ->setClass("btn")
            ->setText('Zobrazit detail')
            ->setLink(function ($row) use ($presenter) {
            return $presenter->link("detail", $row['id']);
        })
            ->setAjax(FALSE);


        $this->setRowFormCallback(function ($values) use ($self, $presenter, $visibility) {
                $user = $presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
                if ($visibility['attended']) {
                    $user->attended = isset($values['attended']) ? true : false;
                }

                if ($visibility['incomeProofPrinted']) {
                    $user->incomeProofPrinted = isset($values['incomeProofPrinted']) ? true : false;
                }

                if (isset($values['paymentMethod'])) {
                    $user->paymentMethod = ($values['paymentMethod'] != null) ? $values['paymentMethod'] : null;
                }

                if ($values['paymentDate']) {
                    $user->paymentDate = \DateTime::createFromFormat('Y-m-d', $values['paymentDate']);
                }
                else {
                    $user->paymentDate = null;
                }

                $CUSTOM_BOOLEAN_COUNT = $presenter->context->parameters['user_custom_boolean_count'];
                for ($i = 0; $i < $CUSTOM_BOOLEAN_COUNT; $i++) {
                    $propertyName = 'customBoolean' . $i;
                    if (isset($values[$propertyName])) {
                        $user->{$propertyName} = isset($values[$propertyName]) ? true : false;;
                    }

                }

                $CUSTOM_TEXT_COUNT = $presenter->context->parameters['user_custom_text_count'];
                for ($i = 0; $i < $CUSTOM_TEXT_COUNT; $i++) {
                    $propertyName = 'customText' . $i;
                    if (isset($values[$propertyName])) {
                        $user->{$propertyName} = $values[$propertyName];
                    }
                }

                $presenter->context->database->flush();
                $self->flashMessage("Záznam byl úspěšně uložen.", "success");

            }
        );

        $this->addAction("makeThemPayBank", "Označit jako zaplacené dnes přes účet")
            ->setCallback(function ($id) use ($self) {
            return $self->handleMakeThemPay($id, 'bank');
        });

        $this->addAction("makeThemPayCash", "Označit jako zaplacené dnes hotově")
            ->setCallback(function ($id) use ($self) {
            return $self->handleMakeThemPay($id, 'cash');
        });

        $this->addAction("attend", "Označit jako přítomné")
            ->setCallback(function ($id) use ($self) {
            return $self->handleAttend($id);
        });

        $this->addAction("printPaymentProofs", "Vytisknout doklad o zaplacení")->setAjax(false)
            ->setCallback(function ($id) use ($presenter) {
            $presenter->redirect('printPaymentProofs!', array('ids' => $id));
        });


    }


    /**
     * @param $ids
     * @param string $method
     */
    public function handleMakeThemPay($ids, $method)
    {
        foreach ($ids as $id) {
            $userToSave = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($id);
            $userToSave->paymentMethod = $method;
            $userToSave->paymentDate = new \DateTime();
        }

        $this->presenter->context->database->flush();

        if (count($ids) > 1) {
            $this->flashMessage("Vybraní uživatelé byli označeni jakože zaplatili.", "success");
        } else {
            $this->flashMessage("Vybraný uživatel byl označen jako zaplacený.", "success");
        }
        $this->redirect("this");
    }


    public function handleAttend($ids)
    {
        foreach ($ids as $id) {
            $userToSave = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($id);
            $userToSave->attended = true;
        }

        $this->presenter->context->database->flush();

        if (count($ids) > 1) {
            $this->flashMessage("Vybraní uživatelé byli označeni jako přítomný na akci.", "success");
        } else {
            $this->flashMessage("Vybraný uživatel byl označen jako přítomný.", "success");
        }
        $this->redirect("this");
    }

    //pretizeni kvuli nefunkcnosti date v row-edit
    public function render()
    {
        $count = $this->getCount();
        $this->getPaginator()->itemCount = $count;
        $this->template->results = $count;
        $this->template->columns = $this['columns']->components;
        $this->template->buttons = $this['buttons']->components;
        $this->template->globalButtons = $this['globalButtons']->components;
        $this->template->subGrids = $this['subGrids']->components;
        $this->template->paginate = $this->paginate;
        $this->template->colsCount = $this->getColsCount();
        $rows = $this->dataSource->getData();
        $this->template->rows = $rows;
        $this->template->primaryKey = $this->primaryKey;
        if ($this->hasActiveRowForm()) {
            $row = $rows[$this->activeRowForm];
            foreach ($row as $name => $value) {
                if ($this->columnExists($name) && !empty($this['columns']->components[$name]->formRenderer)) {
                    $row[$name] = call_user_func($this['columns']->components[$name]->formRenderer, $row);
                }
                if (isset($this['gridForm'][$this->name]['rowForm'][$name])) {
                    $input = $this['gridForm'][$this->name]['rowForm'][$name];
                    if ($input instanceof \Nette\Forms\Controls\SelectBox) {
                        $items = $this['gridForm'][$this->name]['rowForm'][$name]->getItems();
                        if (in_array($row[$name], $items)) {
                            $row[$name] = array_search($row[$name], $items);
                        }
                    }
                }
            }
            foreach ($row as $key => $column) {
                if ($column instanceof \DateTime) {

                    $row[$key] = $column->format('Y-m-d');
                }
            }
            $this['gridForm'][$this->name]['rowForm']->setDefaults($row);
            $this['gridForm'][$this->name]['rowForm']->addHidden($this->primaryKey, $this->activeRowForm);
        }
        if ($this->paginate) {
            $this->template->viewedFrom = ((($this->getPaginator()->getPage() - 1) * $this->perPage) + 1);
            $this->template->viewedTo = ($this->getPaginator()->getLength() + (($this->getPaginator()->getPage() - 1) * $this->perPage));
        }
        $templatePath = !empty($this->templatePath) ? $this->templatePath : __DIR__ . "/../../templates/grid.latte";

        if ($this->getTranslator() instanceof \Nette\Localization\ITranslator) {
            $this->template->setTranslator($this->getTranslator());
        }

        $this->template->setFile($templatePath);
        $this->template->render();
    }


}
