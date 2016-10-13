<?php

namespace SRS\Components;
use \NiftyGrid\Grid;

/**
 * Grid pro správu kategorii
 */
class CategoryGrid extends Grid
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
        $qb->addSelect('c');
        $qb->from('\SRS\Model\Program\Category', 'c');

        $source = new \SRS\SRSDoctrineDataSource($qb, 'id');

        $this->setDataSource($source);
        $numOfResults = 10;
        $this->addColumn('name', 'Název')->setTextFilter()->setAutocomplete($numOfResults)->setWidth('100')->setTextEditable();

        $this->addColumn('registerableRoles', 'Role oprávněné k přihlášení')
            ->setRenderer(function ($row) {
                $roles = $this->em->getRepository('\SRS\Model\Program\Category')->findRegisterableRoles($row->id);
                $rolesStr = "";
                $i = count($roles);
                foreach ($roles as $role) {
                    $rolesStr = $rolesStr . $role->name;
                    $i--;
                    if ($i > 0)
                        $rolesStr = $rolesStr . ", ";
                }
                return $rolesStr;
            });

        $this->addButton("edit", "Upravit")
            ->setClass("btn")
            ->setText('Upravit')
            ->setLink(function ($row) use ($presenter) {
                return $presenter->link("edit", $row['id']);
            })
            ->setAjax(FALSE);

        $this->addButton("delete", "Smazat")
            ->setClass("btn btn-danger confirm")
            ->setText('Smazat')
            ->setLink(function ($row) use ($presenter) {
                return $presenter->link("delete!", $row['id']);
            })
            ->setAjax(FALSE);
    }
}
