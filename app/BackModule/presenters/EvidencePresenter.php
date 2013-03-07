<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 21:07
 * To change this template use File | Settings | File Templates.
 */

namespace BackModule;

class EvidencePresenter extends BasePresenter
{
    protected $resource = 'Evidence';

    /**
     * @var \Nella\Doctrine\Repository
     */
    protected $userRepo;


    protected $evidenceDefaultColumns = array(
        array('name' => 'displayName', 'label' => 'Jméno'),
        array('name' => 'role', 'label' => 'Role'),
        array('name' => 'birthdate', 'label' => 'Věk'),
        array('name' => 'city', 'label' => 'Město'),
        array('name' => 'paid', 'label' => 'Zaplaceno'),
        array('name' => 'paymentMethod', 'label' => 'Platební metoda'),
        array('name' => 'paymentDate', 'label' => 'Datum zaplacení'),
        array('name' => 'incomeProofPrinted', 'label' => 'Vytištěn příjmový doklad?'),
        array('name' => 'attended', 'label' => 'Přítomen'),

    );

    public function startup() {
        parent::startup();
        $this->checkPermissions(\SRS\Model\Acl\Permission::MANAGE);
        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
    }

    public function beforeRender() {
        parent::beforeRender();

    }

    public function renderList() {
        $evidenceColumns = $this->getSession('evidenceColumns');
        if ($evidenceColumns->visibility == null) {
            $columns = array();
            foreach ($this->getAllEvidenceColumns() as $column) {
                $columns[$column['name']] = 1;
            }
            $evidenceColumns->visibility = $columns;
        }
    }

    public function renderDetail($id = null)
    {
        if ($id == null) {
            $this->redirect(':Back:Evidence:list');
        }

        $user = $this->userRepo->find($id);
        if ($user == null) {
            throw new \Nette\Application\BadRequestException("Takový uživatel neexistuje", 404);
        }
        $form = $this->getComponent('evidenceDetailForm');
        $form->bindEntity($user);

        //$user je v template defaultne
        $this->template->dbuser = $user;
        $this->template->customFields = $this->getFilledCustomFields($user);

    }


    protected function createComponentEvidenceGrid() {
       $this->checkSessionConsistency();
       $evidenceColumns = $this->getSession('evidenceColumns');
       return new \SRS\Components\EvidenceGrid($this->context->database, $evidenceColumns->visibility);
   }

    protected function createComponentEvidenceDetailForm()
    {
        return new \SRS\Form\Evidence\EvidenceDetailForm(null, null, $this->context->parameters);
    }

    protected function createComponentColumnForm()
    {
        $form = new \SRS\Form\Evidence\ColumnForm(null, null, $this->getAllEvidenceColumns(), $this->context);
        $form->onSuccess[] = callback($this, 'columnFormSubmitted');
        return $form;
    }

    protected function checkSessionConsistency()
    {
        $evidenceColumns = $this->getSession('evidenceColumns');
        $visibility = $evidenceColumns->visibility;
        foreach ($this->getAllEvidenceColumns() as $column)
        {
            if (!isset($visibility[$column['name']])) {
                $visibility[$column['name']] = true;
            }
        }
        $evidenceColumns->visibility = $visibility;


    }

    public function columnFormSubmitted($form)
    {
        $values = $form->getValues();
        $evidenceColumns = $this->getSession('evidenceColumns');
        $evidenceColumns->visibility = $values;
        $this->redirect('this');
    }


    public function actionGetAttendees()
    {

        $users = $this->userRepo->findAll();
        $usersArray = array();

        foreach($users as $user) {
            $usersArray[] = array('id' => $user->id, 'display_name' => $user->displayName, 'url' =>  $this->link(':Back:evidence:detail', $user->id));
        }

        $json = json_encode($usersArray);
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();

    }

    public function handlePrintIncomeProof($ids = array())
    {
        $printer = $this->context->printer;

        $users = array();
        foreach ($ids as $userId) {
            $users[] = $user = $this->userRepo->find($userId);
            $user->incomeProofPrinted = true;
        }
        $this->context->database->flush();
        //$this->redirect('this');
        $printer->printIncomeProofs($users);
    }

    public function handlePrintAccountProof($userId)
    {
        $printer = $this->context->printer;
        $user = $this->userRepo->find($userId);
        $printer->printAccountProofs(array($user));
    }

    protected function getAllEvidenceColumns()
    {
        $columns = $this->evidenceDefaultColumns;
        $customColumns = $this->getFilledCustomFields();

        foreach ($customColumns as $cColumn)
        {
            $columns[] = array('name' => $cColumn['property'], 'label' => $cColumn['name']);
        }
        return $columns;
    }




    protected function getFilledCustomFields($user = null)
    {
        $fields = array();
        $booleansCount = $this->context->parameters['user_custom_boolean_count'];
        $textsCount = $this->context->parameters['user_custom_text_count'];

        for ($i = 0; $i < $booleansCount; $i++) {
            $settingsColumn = 'user_custom_boolean_'.$i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            $propertyName = 'customBoolean'.$i;
            if ($dbvalue != '') {
                if ($user) {
                    $fields[] = array('property' => $propertyName,'name' => $dbvalue, 'value'=> $user->getCustomBoolean($i), 'type' => 'boolean');
                }
                else {
                    $fields[] = array('property' => $propertyName, 'name' => $dbvalue, 'type' => 'boolean');
                }
            }
        }

        for ($i = 0; $i < $textsCount; $i++) {
            $settingsColumn = 'user_custom_text_'.$i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            $propertyName = 'customText'.$i;
            if ($dbvalue != '') {
                if ($user) {
                     $fields[] = array('property' => $propertyName, 'name' => $dbvalue, 'value' => $user->getCustomText($i), 'type' => 'text');
                }
                else {
                    $fields[] = array('property' => $propertyName, 'name' => $dbvalue, 'type' => 'text');
                }
            }
        }
        return $fields;




    }

}
