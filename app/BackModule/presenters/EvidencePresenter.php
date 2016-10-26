<?php
/**
 * Date: 26.1.13
 * Time: 21:07
 * Author: Michal Májský
 */

namespace BackModule;

/**
 * presenter obsluhujici balicek Evidence ucastniku
 */
class EvidencePresenter extends BasePresenter
{
    protected $resource = 'Uživatelé';

    /**
     * @var \Nella\Doctrine\Repository
     */
    protected $userRepo;


    protected $evidenceDefaultColumns = array(
        array('name' => 'displayName', 'label' => 'Jméno'),
        array('name' => 'username', 'label' => 'Uživatelské jméno'),
        array('name' => 'roles', 'label' => 'Role'),
        array('name' => 'membership', 'label' => 'Členství'),
        array('name' => 'birthdate', 'label' => 'Věk'),
        array('name' => 'city', 'label' => 'Město'),
        array('name' => 'fee', 'label' => 'Cena'),
        array('name' => 'paymentMethod', 'label' => 'Platební metoda'),
        array('name' => 'variableSymbol', 'label' => 'Variabilní symbol'),
        array('name' => 'paymentDate', 'label' => 'Zaplaceno dne'),
        array('name' => 'incomeProofPrintedDate', 'label' => 'Doklad vytištěn dne'),
        array('name' => 'firstLogin', 'label' => 'Registrace'),
        array('name' => 'attended', 'label' => 'Přítomen'),
        array('name' => 'approved', 'label' => 'Schválený')
    );

    public function startup()
    {
        parent::startup();
        $this->checkPermissions(\SRS\Model\Acl\Permission::MANAGE);
        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
    }

    public function beforeRender()
    {
        parent::beforeRender();

    }

    public function renderList($ids = array())
    {
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

        $user->generateVariableSymbol($this->context->database);

        //$user je v template defaultne
        $this->template->dbuser = $user;
        $this->template->customFields = $this->getFilledCustomFields($user);
        $this->template->paymentMethods = $this->context->parameters['payment_methods'];
        $this->template->pays = $user->countFee()['fee'] != 0;
    }

    public function renderEdit($id = null)
    {
        if ($id == null) {
            $this->redirect(':Back:Evidence:list');
        }

        $user = $this->userRepo->find($id);
        if ($user == null) {
            throw new \Nette\Application\BadRequestException("Takový uživatel neexistuje", 404);
        }

        $user->generateVariableSymbol($this->context->database);

        $form = $this->getComponent('evidenceEditForm');
        $form->bindEntity($user);

        //$user je v template defaultne
        $this->template->dbuser = $user;
        $this->template->customFields = $this->getFilledCustomFields($user);
        $this->template->pays = $user->countFee()['fee'] != 0;
    }

    public function renderEditRoles($ids = array()) {
        if (count($ids) == 0)
            $this->redirect(':Back:Evidence:list');

        $users = array();
        foreach ($ids as $id) {
            $user = $this->userRepo->find($id);
            if ($user == null) {
                throw new \Nette\Application\BadRequestException("Takový uživatel neexistuje", 404);
            }
            $users[] = $user;
        }

        $form = $this->getComponent('evidenceEditRolesForm');
        $form->setDefaults(array (
            'ids' => implode(",",$ids)
        ));

        $this->template->users = $users;
     }

    protected function createComponentEvidenceGrid()
    {
        $this->checkSessionConsistency();
        $evidenceColumns = $this->getSession('evidenceColumns');
        return new \SRS\Components\EvidenceGrid($this->context->database, $evidenceColumns->visibility);
    }

    protected function createComponentEvidenceEditForm()
    {
        return new \SRS\Form\Evidence\EvidenceEditForm(null, null, $this->context->parameters, $this->context->database, $this->dbsettings);
    }

    protected function createComponentEvidenceEditRolesForm()
    {
        return new \SRS\Form\Evidence\EvidenceEditRolesForm(null, null, $this->context->database);
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
        foreach ($this->getAllEvidenceColumns() as $column) {
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

        foreach ($users as $user) {
            $usersArray[] = array('id' => $user->id, 'display_name' => $user->displayName, 'url' => $user->id);
        }

        $json = json_encode($usersArray);
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();

    }

    public function handlePrintPaymentProof($userId)
    {
        $user = $this->userRepo->find($userId);
        $user->incomeProofPrintedDate = new \DateTime();
        $this->context->database->flush();

        $printer = $this->context->printer;
        $printer->printPaymentProofs(array($user));
    }

    public function handlePrintPaymentProofs($ids = array())
    {
        $users = [];

        foreach ($ids as $userId) {
            $user = $this->userRepo->find($userId);
            if ($user->paymentDate == null)
                continue;
            $users[] = $user;
            $user->incomeProofPrintedDate = new \DateTime();
        }
        $this->context->database->flush();
        $printer = $this->context->printer;
        $printer->printPaymentProofs($users);
    }

    protected function getAllEvidenceColumns()
    {
        $columns = $this->evidenceDefaultColumns;
        $customColumns = $this->getFilledCustomFields();

        foreach ($customColumns as $cColumn) {
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
            $settingsColumn = 'user_custom_boolean_' . $i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            $propertyName = 'customBoolean' . $i;
            if ($dbvalue != '') {
                if ($user) {
                    $fields[] = array('property' => $propertyName, 'name' => $dbvalue, 'value' => $user->getCustomBoolean($i), 'type' => 'boolean');
                } else {
                    $fields[] = array('property' => $propertyName, 'name' => $dbvalue, 'type' => 'boolean');
                }
            }
        }

        for ($i = 0; $i < $textsCount; $i++) {
            $settingsColumn = 'user_custom_text_' . $i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            $propertyName = 'customText' . $i;
            if ($dbvalue != '') {
                if ($user) {
                    $fields[] = array('property' => $propertyName, 'name' => $dbvalue, 'value' => $user->getCustomText($i), 'type' => 'text');
                } else {
                    $fields[] = array('property' => $propertyName, 'name' => $dbvalue, 'type' => 'text');
                }
            }
        }
        return $fields;
    }

}
