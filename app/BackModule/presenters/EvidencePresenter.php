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

    public function startup() {
        parent::startup();
        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
    }

    public function beforeRender() {
        parent::beforeRender();

    }

    public function renderList() {

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
       return new \SRS\Components\EvidenceGrid($this->context->database);
   }

    protected function createComponentEvidenceDetailForm()
    {
        return new \SRS\Form\Evidence\EvidenceDetailForm();
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

    protected function getFilledCustomFields($user)
    {
        $fields = array();
        $booleansCount = $this->context->parameters['user_custom_boolean_count'];
        $textsCount = $this->context->parameters['user_custom_text_count'];

        for ($i = 0; $i < $booleansCount; $i++) {
            $settingsColumn = 'user_custom_boolean_'.$i;
            //$dbColumn = 'customBoolean'.$i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            if ($dbvalue != '') {
                $fields[] = array('name' => $dbvalue, 'value'=> $user->getCustomBoolean($i));
            }
        }

        for ($i = 0; $i < $textsCount; $i++) {
            $settingsColumn = 'user_custom_text_'.$i;
            //$dbColumn = 'customText'.$i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            if ($dbvalue != '') {
                $fields[] = array( 'name' => $dbvalue, 'value' => $user->getCustomText($i));
            }
        }
        return $fields;




    }



}
