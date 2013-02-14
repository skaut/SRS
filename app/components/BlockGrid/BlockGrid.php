<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 30.10.12
 * Time: 21:08
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Components;
use \NiftyGrid\Grid;
use \Doctrine\ORM\Query\Expr;

/**
 * Grid pro správu uživatelů a práv
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
        $this->templatePath = __DIR__.'/template.latte';
    }

    protected function configure($presenter)
    {
        $blockRepo = $this->em->getRepository('\SRS\Model\Program\Block');
        $qb = $this->em->createQueryBuilder();
        $qb->addSelect('b');
        $qb->addSelect('lector');
        $qb->from('\SRS\Model\Program\Block', 'b');
        $qb->leftJoin('b.lector','lector');
        if (!$presenter->context->user->isAllowed('Program', 'Spravovat Všechny Programy' )) {
            $qb->where(new \Doctrine\ORM\Query\Expr\Comparison('lector.id', '=', $presenter->context->user->id ));
        }
        $source = new \SRS\SRSDoctrineDataSource($qb, 'id');

        $lectors = $this->em->getRepository('\SRS\Model\Acl\Role')->findApprovedUsersInRole('lektor');
        $lectorChoices = \SRS\Form\EntityForm::getFormChoices($lectors, 'id', 'lastName');
        $basicBlockDuration = $this->em->getRepository('\SRS\Model\Settings')->get('basic_block_duration');

        $this->setDataSource($source);
        $numOfResults = 10;
        $this->addColumn('name', 'Název')->setTextFilter()->setAutocomplete($numOfResults);
        $lectorColumn = $this->addColumn('lector', 'Lektor')
                ->setRenderer(function($row) {
                return $row->lector['lastName'];
            });
        if ($presenter->context->user->isAllowed('Program', 'Spravovat Všechny Programy' )) {
        $lectorColumn->setSelectFilter($lectorChoices);
        }
                //->setSelectFilter($lectorChoices);
        $this->addColumn('duration', 'Délka')
            ->setRenderer(function($row) use ($basicBlockDuration) {
            return $row->duration * $basicBlockDuration . ' minut';
        });
        $this->addColumn('capacity', 'Kapacita');
        $this->addColumn('program_count', 'Počet zařazení')
            ->setRenderer(function($row) use ($basicBlockDuration, $blockRepo) {
            return $blockRepo->find($row->id)->programs->count();
        });


        $this->addButton("detail", "Zobrazit detail")
            ->setClass("btn")
            ->setText('Zobrazit detail')
            ->setLink(function($row) use ($presenter){return $presenter->link("detail", $row['id']);})
            ->setAjax(FALSE);


        $this->addButton("edit", "Upravit")
            ->setClass("btn btn-warning")
            ->setText('Upravit')
            ->setLink(function($row) use ($presenter){return $presenter->link("edit", $row['id']);})
            ->setAjax(FALSE);


        $this->addButton("delete", "Smazat")
            ->setClass("btn btn-danger confirm")
            ->setText('Smazat')
            ->setLink(function($row) use ($presenter){return $presenter->link("delete!", $row['id']);})
            ->setAjax(FALSE);

    }







}
