<?php

namespace SRS\Components;
use \NiftyGrid\Grid;

/**
 * Grid pro správu místností
 */
class RoomGrid extends Grid
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;


    /**
     * @param \Nette\ComponentModel\IContainer $em
     */
    public function __construct($em)
    {
        parent::__construct();
        $this->em = $em;
        $this->templatePath = __DIR__ . '/template.latte';
    }

    protected function configure($presenter)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->addSelect('r');
        $qb->from('\SRS\Model\Program\Room', 'r');

        $source = new \SRS\SRSDoctrineDataSource($qb, 'id');

        $this->setDataSource($source);
        $numOfResults = 10;
        $this->addColumn('name', 'Název')->setTextFilter()->setAutocomplete($numOfResults)->setWidth('100')->setTextEditable();

        $self = $this;

        $this->setRowFormCallback(function ($values) use ($self, $presenter) {
            $roomToSave = $presenter->context->database->getRepository('\SRS\Model\Program\Room')->find($values['id']);
            $roomToSave->name = $values['name'];
            $presenter->context->database->flush();
            $self->flashMessage("Záznam byl úspěšně uložen.", "success");
        }
        );

        $this->addButton(Grid::ROW_FORM, "Změnit")
            ->setClass("fast-edit");


        $this->addButton("delete", "Smazat")
            ->setClass("btn btn-danger confirm")
            ->setText('Smazat')
            ->setLink(function ($row) use ($presenter) {
                return $presenter->link("delete!", $row['id']);
            })
            ->setAjax(FALSE);
    }
}
