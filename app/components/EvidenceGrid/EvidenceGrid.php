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
        $this->templatePath = __DIR__.'/template.latte';
        $this->columnsVisibility = $columnsVisibility;
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


        $self = $this;
        if ($this->columnsVisibility['displayName'])
            $this->addColumn('displayName', 'Jméno')->setTextFilter()->setAutocomplete($numOfResults);

        if ($this->columnsVisibility['role'])
            $this->addColumn('role', 'Role')
                ->setRenderer(function($row) {
                    return $row->role['name'];
                })
                ->setSelectFilter($rolesGrid);

        if ($this->columnsVisibility['birthdate'])
            $this->addColumn('birthdate', 'Věk')
                ->setRenderer(function($row) use ($today) {
                    $interval = $today->diff($row->birthdate);
                    return $interval->y;
                });

        if ($this->columnsVisibility['city'])
            $this->addColumn('city', 'Město')
                ->setTextFilter()
                ->setAutocomplete($numOfResults);

        if ($this->columnsVisibility['paid'])
            $this->addColumn('paid', 'Zaplaceno')
                ->setBooleanFilter()
                ->setBooleanEditable()
                ->setRenderer(function($row) {
                    return \SRS\Helpers::renderBoolean($row->paid);
            });

        $paymentMethods = $presenter->context->parameters['payment_methods'];

        if ($this->columnsVisibility['paymentMethod'])
            $this->addColumn('paymentMethod', 'Platební metoda')
                ->setSelectFilter($paymentMethods)
                ->setSelectEditable($paymentMethods, 'nezadáno')
                ->setRenderer(function ($row) use ($paymentMethods) {
                    if ($row->paymentMethod == null || $row->paymentMethod == '') return '';
                    return $paymentMethods[$row->paymentMethod];
                });

        if ($this->columnsVisibility['attended'])
            $this->addColumn('attended', 'Přítomen')
                ->setBooleanFilter()
                ->setBooleanEditable()
                ->setRenderer(function($row) {
                    return \SRS\Helpers::renderBoolean($row->attended);
            });

        $CUSTOM_BOOLEAN_COUNT = $presenter->context->parameters['user_custom_boolean_count'];
        for ($i = 0; $i < $CUSTOM_BOOLEAN_COUNT; $i++) {
            $column = 'user_custom_boolean_'.$i;
            $dbvalue = $this->dbsettings->get($column);
            $propertyName = 'customBoolean'.$i;

            if ($dbvalue != '' && $this->columnsVisibility[$propertyName])
            {
                $this->addColumn($propertyName, $this->dbsettings->get($column))
                    ->setBooleanFilter()
                    ->setRenderer(function($row) use ($i) {
                    return \SRS\Helpers::renderBoolean($row->{"customBoolean$i"});
                });
            }
        }

        $CUSTOM_TEXT_COUNT = $presenter->context->parameters['user_custom_text_count'];
        for ($i = 0; $i < $CUSTOM_TEXT_COUNT; $i++) {
            $column = 'user_custom_text_'.$i;
            $dbvalue = $this->dbsettings->get($column);
            $propertyName = 'customText'.$i;

            if ($dbvalue != '' && $this->columnsVisibility[$propertyName])
            {
                $this->addColumn($propertyName, $this->dbsettings->get($column))
                    ->setTextFilter();
            }
        }

        $this->addButton(Grid::ROW_FORM, "Řádková editace")
            ->setClass("fast-edit");

        $this->addButton("detail", "Detail")
            ->setClass("btn")
            ->setText('Zobrazit detail')
            ->setLink(function($row) use ($presenter){return $presenter->link("detail", $row['id']);})
            ->setAjax(FALSE);





        $this->setRowFormCallback(function($values) use ($self, $presenter){
                $user = $presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
                $user->paid = isset($values['paid']) ? true : false;
                $user->attended = isset($values['attended']) ? true : false;
                $user->paymentMethod = ($values['paymentMethod'] != null) ? $values['paymentMethod'] : null;
                //$user->setProperties($values, $presenter->context->database);
                $presenter->context->database->flush();
                $self->flashMessage("Záznam byl úspěšně uložen.","success");

            }
        );

        $this->addAction("makeThemPay","Označit jako zaplacené")
            ->setCallback(function($id) use ($self){return $self->handleMakeThemPay($id);});

        $this->addAction("attend","Označit jako přítomné")
            ->setCallback(function($id) use ($self){return $self->handleAttend($id);});



    }



    public function handleMakeThemPay($ids)
    {
        foreach ($ids as $id ) {
            $userToSave = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($id);
            $userToSave->paid = true;
        }

        $this->presenter->context->database->flush();

        if(count($ids) > 1){
            $this->flashMessage("Vybraní uživatelé byli označeni jakože zaplatili.","success");
        }else{
            $this->flashMessage("Vybraný uživatel byl označen jako zaplacený.","success");
        }
        $this->redirect("this");
    }

    public function handleAttend($ids)
    {
        foreach ($ids as $id ) {
            $userToSave = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($id);
            $userToSave->paid = true;
        }

        $this->presenter->context->database->flush();

        if(count($ids) > 1){
            $this->flashMessage("Vybraní uživatelé byli označeni jako přítomný na akci.","success");
        }else{
            $this->flashMessage("Vybraný uživatel byl označen jako přítomný.","success");
        }
        $this->redirect("this");
    }

//    public function renderBoolean($bool)
//    {
//        if ($bool) return 'ANO';
//        return 'NE';
//    }








}
