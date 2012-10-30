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

/**
 * Grid pro správu uživatelů
 */
class UserGrid extends Grid
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $db;

    /**
     * @param \Doctrine\ORM\EntityManager
     */
    public function __construct($db)
    {
        parent::__construct();
        $this->db = $db;
    }

    protected function configure($presenter)
    {
        $source = new \NiftyGrid\DataSource\DoctrineDataSource($this->db->createQueryBuilder()->add('select', 'u')->add('from', '\SRS\Model\User u'), 'u_id');
        $this->setDataSource($source);
        $this->addColumn('u_username', 'Username');
        $this->addColumn('u_nickName', 'Přezdívka');
        $this->addColumn('u_firstName', 'Jméno');
        $this->addColumn('u_lastName', 'Příjmení');
        $this->addColumn('u_sex', 'datum nar.');
    }

}
