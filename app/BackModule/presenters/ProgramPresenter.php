<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 21:07
 * To change this template use File | Settings | File Templates.
 */

namespace BackModule;

class ProgramPresenter extends BasePresenter
{

    protected $resource = 'Program';

    /**
     * @var \SRS\Model\Program\ProgramRepository
     */
    protected $programRepo;

    protected $basicBlockDuration;

    public function startup() {
        parent::startup();
        $this->programRepo = $this->context->database->getRepository('\SRS\Model\Program\Program');
        $this->basicBlockDuration = $this->dbsettings->get('basic_block_duration');
    }

    public function renderDefault() {

    }

    /**
     * @param bool $userAttending chceme Informace o tom, zda se prihlaseny uzivatel ucastni programu?
     */
    public function actionGet($userAttending = false) {
        if ($userAttending == true) {
            if (!$this->context->user->isLoggedIn()) {
                throw new \Nette\Security\AuthenticationException('Uživatel musí být přihlášen');
            }
            $user = $this->context->database->getRepository('\SRS\Model\User')->find($this->context->user->id);
            $programs = $this->programRepo->findAllForJson($this->basicBlockDuration, $user);
        }
        else {
            $programs = $this->programRepo->findAllForJson($this->basicBlockDuration);
        }
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $json = $serializer->serialize($programs, 'json');
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();
    }


    public function actionSet($data) {
        $program = $this->programRepo->saveFromJson($data, $this->basicBlockDuration);
        $response = new \Nette\Application\Responses\JsonResponse(array('id' => $program->id));
        $this->sendResponse($response);
        $this->terminate();
    }

    public function actionDelete($id) {

        $program = $this->programRepo->find($id);
        if ($program != null) {
            $this->context->database->remove($program);
            $this->context->database->flush();
            $response = new \Nette\Application\Responses\JsonResponse(array('status' => 'ok'));
        }
        else {
            $response = new \Nette\Application\Responses\JsonResponse(array('status' => 'error'));
        }
        $this->sendResponse($response);
        $this->terminate();

    }


    public function actionGetOptions() {
        $blocks = $this->context->database->getRepository('\SRS\Model\Program\Block')->findAll();
        $result = array();

        foreach ($blocks as $block) {
            $result[$block->id] = array('id' => $block->id, 'name' => $block->name, 'tools' => $block->tools, 'location' => $block->location,  'capacity' => $block->capacity, 'duration' => $block->duration, 'perex' => $block->perex, 'description' => $block->description);
            if (isset($block->lector) && $block->lector != null) {
                $result[$block->id]['lector'] = "{$block->lector->displayName}";
                $result[$block->id]['lector_about'] = $block->lector->about;
            }
            else {
                $result[$block->id]['lector'] = 'Nezadán';
                 $result[$block->id]['lector_about'] = '';
            }
        }
        $response = new \Nette\Application\Responses\JsonResponse($result);
        $this->sendResponse($response);
        $this->terminate();

    }

    public function actionGetCalendarConfig()
    {
        $calConfig = array();
        $fromDate = $this->dbsettings->get('seminar_from_date');
        $datePieces = explode('-', $fromDate);
        $calConfig['year'] = $datePieces[0];
        $calConfig['month'] = $datePieces[1]-1; //fullcalendar je zerobased
        $calConfig['date'] = $datePieces[2];
        $calConfig['basic_block_duration'] = $this->dbsettings->get('basic_block_duration');
        if ((bool) $this->dbsettings->get('is_allowed_modify_schedule') && $this->user->isAllowed($this->resource, 'Upravovat harmonogram')) {
            $calConfig['is_allowed_modify_schedule'] = true;
        }
        else {
            $calConfig['is_allowed_modify_schedule'] = false;
        }

        $calConfig['is_allowed_log_in_programs'] = (bool) $this->dbsettings->get('is_allowed_log_in_programs') && $this->user->isAllowed($this->resource, 'Vybírat si programy');

        $response = new \Nette\Application\Responses\JsonResponse($calConfig);
        $this->sendResponse($response);
        $this->terminate();

    }

    /**
     * @param integer $id programID
     */
    public function actionAttend($id) {
        if (!$this->context->user->isLoggedIn()) { //uzivatel neni prihlasen
            $message = array('status' => 'error', 'message' => 'Uživatel není přihlášen');
        }
        else { //uzivatel je prihlasen
            $program = $this->programRepo->find($id);
            if ($program == null) { //ID programu neexistuje
                $message = array('status' => 'error', 'message' => 'Program s tímto id neexistuje');
            }
            else { // ID programu existuje

                if ($program->block == null) { // program nema prirazeny blok
                    $message = array('status' => 'error', 'message' => 'Na blok, který nemá přiřazen žádný program se nelze přihlásit.' );
                }
                else { // program ma prirazeny blok
                    if ($program->block->capacity > $program->attendees->count()) { //program ma volne misto
                        $userRepo = $this->context->database->getRepository('\SRS\Model\User');
                        $user = $userRepo->find($this->context->user->id);
                        if ($user->hasOtherProgram($program, $this->basicBlockDuration)) {
                            $message = array('status' => 'error', 'message' => 'V tuto dobu máte přihlášený již jiný program.' );
                        }
                        else {
                            if (!$program->attendees->contains($user)) { // uzivatle na program jeste neni prihlasen
                                $program->attendees->add($user);
                                $this->context->database->flush();
                                $message = array('status' => 'success', 'message' => 'Program úspěšně přihlášen.' );
                            }
                            else { // uzivatel uz je na program prihlasen
                                $message = array('status' => 'error', 'message' => 'Uživatel je již přihlášen');
                            }
                        }
                    }
                    else { // program je plny
                        $message = array('status' => 'error', 'message' => 'Kapacita programu je již plná');
                    }
                }
            }
        }
        $program->prepareForJson(null, $this->basicBlockDuration);
        $message['event']['attendees_count'] = $program->attendeesCount;
        $response = new \Nette\Application\Responses\JsonResponse($message);
        $this->sendResponse($response);
        $this->terminate();
    }

    /**
     * @param integer $id programID
     */
    public function actionUnattend($id) {
        if (!$this->context->user->isLoggedIn()) {
            $message = array('status' => 'error', 'message' => 'Uživatel není přihlášen');
        }
        else {
            $program = $this->programRepo->find($id);
            if ($program == null) {
                $message = array('status' => 'error', 'message' => 'Program s tímto id neexistuje');
            }
            else {
                    $user = $this->context->database->getRepository('\SRS\Model\User')->find($this->context->user->id);
                    if ($program->attendees->contains($user)) {
                        $program->attendees->removeElement($user);
                        $this->context->database->flush();
                        $message = array('status' => 'success', 'message' => 'Program úspěšně odhlášen.' );
                    }
                    else {
                        $message = array('status' => 'error', 'message' => 'Program vůbec nebyl přihlášen');
                    }
            }
        }
        $program->prepareForJson(null, $this->basicBlockDuration);
        $message['event']['attendees_count'] = $program->attendeesCount;
        $response = new \Nette\Application\Responses\JsonResponse($message);
        $this->sendResponse($response);
        $this->terminate();
    }
}

