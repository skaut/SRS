<?php
/**
 * Date: 30.10.12
 * Time: 21:08
 * Author: Michal Májský
 */
namespace SRS\Components;

use \NiftyGrid\Grid;
use \Doctrine\ORM\Query\Expr;
use SRS\Model\Acl\Resource;
use SRS\Model\Acl\Permission;

/**
 * Grid pro správu programových bloků
 */
class BlockGrid extends Grid
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
        $blockRepo = $this->em->getRepository('\SRS\Model\Program\Block');
        $qb = $this->em->createQueryBuilder();
        $qb->addSelect('b');
        $qb->addSelect('lector');
        $qb->addSelect('category');
        $qb->addSelect('room');
        $qb->from('\SRS\Model\Program\Block', 'b');
        $qb->leftJoin('b.lector', 'lector');
        $qb->leftJoin('b.category', 'category');
        $qb->leftJoin('b.room', 'room');

        if (!$presenter->context->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS)) {
            $qb->where(new \Doctrine\ORM\Query\Expr\Comparison('lector.id', '=', $presenter->context->user->id));
        }
        $source = new \SRS\SRSDoctrineDataSource($qb, 'id');

        $lectors = $this->em->getRepository('\SRS\Model\Acl\Role')->findApprovedUsersInRole('lektor');
        $lectorChoices = \SRS\Form\EntityForm::getFormChoices($lectors, 'id', 'displayName');

        $basicBlockDuration = $this->em->getRepository('\SRS\Model\Settings')->get('basic_block_duration');

        $categories = $this->em->getRepository('\SRS\Model\Program\Category')->findAll();
        $categoriesChoices = \SRS\Form\EntityForm::getFormChoices($categories, 'id', 'name');

        $rooms = $this->em->getRepository('\SRS\Model\Program\Room')->findAll();
        $roomsChoices = \SRS\Form\EntityForm::getFormChoices($rooms, 'id', 'name');

        $this->setDataSource($source);
        $numOfResults = 10;


        $this->addColumn('name', 'Název')->setTextFilter()->setAutocomplete($numOfResults);

        $this->addColumn('category', 'Kategorie')
            ->setRenderer(function ($row) {
                return $row->category['name'];
            })
            ->setSelectFilter($categoriesChoices);

        $lectorColumn = $this->addColumn('lector', 'Lektor')
            ->setRenderer(function ($row) {
                return $row->lector['displayName'];
            });
        if ($presenter->context->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS)) {
            $lectorColumn->setSelectFilter($lectorChoices);
        }

        $this->addColumn('room', 'Místnost')
            ->setRenderer(function ($row) {
                return $row->room['name'];
            })
            ->setSelectFilter($roomsChoices);

        $this->addColumn('duration', 'Délka')
            ->setRenderer(function ($row) use ($basicBlockDuration) {
                return $row->duration * $basicBlockDuration . ' minut';
            });

        $this->addColumn('capacity', 'Kapacita');

        $this->addColumn('program_count', 'Počet zařazení')->setSortable(false)
            ->setRenderer(function ($row) use ($basicBlockDuration, $blockRepo) {
                return $blockRepo->find($row->id)->programs->count();
            });


        $this->addButton("detail", "Zobrazit detail")
            ->setClass("btn")
            ->setText('Zobrazit detail')
            ->setLink(function ($row) use ($presenter) {
                return $presenter->link("detail", $row['id']);
            })
            ->setAjax(FALSE);


        $this->addButton("edit", "Upravit")
            ->setClass("btn btn-warning")
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
