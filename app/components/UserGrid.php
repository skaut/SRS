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
class UserGrid extends Grid
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
    }

    protected function configure($presenter)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->addSelect('u');
        $qb->addSelect('role');
        $qb->from('\SRS\Model\User', 'u');
        $qb->leftJoin('u.roles','role'); //'WITH', 'role.standAlone=1');
        $query = $qb->getQuery();
        $users = $query->getResult();
        \Nette\Diagnostics\Debugger::dump($qb->getQuery()->getScalarResult());
        //\Nette\Diagnostics\Debugger::dump($users);

        $roles = $this->em->getRepository('\SRS\Model\Role')->findAll();
        $rolesGrid = array();

        foreach ($roles as $role) {
            $rolesGrid[$role->id] = $role->name;
        }
        $source = new \NiftyGrid\DataSource\DoctrineDataSource($qb, 'u_id');

        $this->setDataSource($source);
        $numOfResults = 10;
        $this->addColumn('u_username', 'Username')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('u_nickName', 'Přezdívka')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('u_firstName', 'Jméno')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('u_lastName', 'Příjmení')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('role_id', 'Role')
            ->setRenderer(function($row) {
                return $row->role_name;
            })
            ->setSelectFilter($rolesGrid)
            ->setSelectEditable($rolesGrid);

        $this->setRowFormCallback(function($values) {
            \Nette\Diagnostics\Debugger::dump($values);
        });

        $this->addButton(Grid::ROW_FORM, "Změnit")
            ->setClass("fast-edit");
    }

}
