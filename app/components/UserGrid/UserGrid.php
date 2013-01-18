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
        $this->templatePath = __DIR__.'/template.latte';
    }

    protected function configure($presenter)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->addSelect('u');
        $qb->addSelect('role');
        $qb->from('\SRS\Model\User', 'u');
        $qb->leftJoin('u.role','role'); //'WITH', 'role.standAlone=1');



       // $query = $this->em->createQuery("SELECT u, role FROM \SRS\Model\User u LEFT JOIN u.roles role WHERE u.username LIKE 'srs%'");
        //$query->getResult();
//        $lol = $user->roles->filter(function($role) {
//            return $role->standAlone == true;
//        });

        $roles = $this->em->getRepository('\SRS\Model\Acl\Role')->findAll();

        $rolesGrid = array();

        $userToSave = $presenter->context->database->getRepository('\SRS\Model\User')->find(1);




        foreach ($roles as $role) {
            $rolesGrid[$role->id] = $role->name;
        }
        $source = new \SRS\SRSDoctrineDataSource($qb, 'id');

        $this->setDataSource($source);
        $numOfResults = 10;
        $this->addColumn('username', 'Username')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('nickName', 'Přezdívka')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('firstName', 'Jméno')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('lastName', 'Příjmení')->setTextFilter()->setAutocomplete($numOfResults);
        $this->addColumn('role', 'Role')
            ->setRenderer(function($row) {
                return $row->role['name'];
            })
            ->setSelectFilter($rolesGrid)

            ->setSelectEditable($rolesGrid);
        $this->addColumn('approved', 'Schválený')->setBooleanFilter()->setBooleanEditable()
            ->setRenderer(function($row) {
                return $row->approved ? 'Ano':'Ne';
        });


        $self = $this;



        $this->setRowFormCallback(function($values) use ($self, $presenter){
                $userToSave = $presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
                $newRole = $presenter->context->database->getRepository('SRS\Model\Acl\Role')->find($values['role']);
                $userToSave->role = $newRole;
                $userToSave->approved = isset($values['approved']) ? 1 : 0;
                $presenter->context->database->flush();

                $self->flashMessage("Záznam byl úspěšně uložen.","success");

            }
        );

        $this->addButton(Grid::ROW_FORM, "Změnit")
            ->setClass("fast-edit");


        $this->addAction("approve","Schválit")
            ->setCallback(function($id) use ($self){return $self->handleApprove($id);});
    }


    public function handleApprove($ids)
    {
        foreach ($ids as $id ) {
            $userToSave = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($id);
            $userToSave->approved = True;
        }

        $this->presenter->context->database->flush();

        if(count($ids) > 1){
            $this->flashMessage("Vybraní uživatelé byli schváleni.","success");
        }else{
            $this->flashMessage("Vybraný uživatel byl schválen.","success");
        }
        $this->redirect("this");
    }




}
