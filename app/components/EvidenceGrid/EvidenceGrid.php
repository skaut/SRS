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
class EvidenceGrid extends Grid
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;



    public function __construct($em)
    {
        parent::__construct();
        $this->em = $em;
        $this->templatePath = __DIR__.'/template.latte';
    }

    protected function configure($presenter)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->addSelect('u');
        $qb->addSelect('role');
        $qb->from('\SRS\Model\User', 'u');
        $qb->leftJoin('u.role','role'); //'WITH', 'role.standAlone=1');


        $roles = $this->em->getRepository('\SRS\Model\Acl\Role')->findAll();

        $rolesGrid = array();

        $numOfResults = 10;
        $today = new \DateTime('now');

        foreach ($roles as $role) {
            $rolesGrid[$role->id] = $role->name;
        }
        $source = new \SRS\SRSDoctrineDataSource($qb, 'id');
        $this->setDataSource($source);




        $this->addColumn('displayName', 'Jméno')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('role', 'Role')
            ->setRenderer(function($row) {
                return $row->role['name'];
            })
            ->setSelectFilter($rolesGrid);

        $this->addColumn('birthdate', 'Věk')
            ->setRenderer(function($row) use ($today) {
                $interval = $today->diff($row->birthdate);
                return $interval->y;
            });




    }







}
