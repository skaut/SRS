<?php
/**
 * Date: 26.1.13
 * Time: 21:07
 * Author: Michal Májský
 */

namespace BackModule\ProgramModule;
use \SRS\Model\Acl\Permission;
use SRS\Model\Acl\Resource;

/**
 * Vytvari Api pro tvorbu harmonogramu a zapis programu
 */
class ApiPresenter extends \BackModule\BasePresenter
{
    protected $resource = Resource::PROGRAM;

    /**
     * @var \SRS\Model\Program\BlockRepository
     */
    protected $blockRepo;

    /**
     * @var \SRS\Model\Program\ProgramRepository
     */
    protected $programRepo;


    protected $basicBlockDuration;

    public function startup()
    {
        parent::startup();

        $this->blockRepo = $this->context->database->getRepository('\SRS\Model\Program\Block');
        $this->programRepo = $this->context->database->getRepository('\SRS\Model\Program\Program');
        $this->basicBlockDuration = $this->dbsettings->get('basic_block_duration');
    }

    /**
     * @param bool $userAttending chceme Informace o tom, zda se prihlaseny uzivatel ucastni programu?
     * @throws \Nette\Security\AuthenticationException
     */
    public function actionGetPrograms($userAttending = false, $onlyAssigned = false, $userAllowed = false)
    {
        if ($userAttending || $userAllowed) {
            if (!$this->context->user->isLoggedIn()) {
                throw new \Nette\Security\AuthenticationException('Uživatel musí být přihlášen');
            }
            $dbuser = $this->context->database->getRepository('\SRS\Model\User')->find($this->context->user->id);
            $user = $this->context->user;
        } else {
            $dbuser = null;
            $user = null;
        }

        $programs = $this->programRepo->findAllForJson($this->basicBlockDuration, $user, $dbuser, $onlyAssigned, $userAllowed);
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $json = $serializer->serialize($programs, 'json');
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();
    }

    public function actionSetProgram($data)
    {
        $program = $this->programRepo->saveFromJson($data, $this->basicBlockDuration);
        $response = new \Nette\Application\Responses\JsonResponse(array('id' => $program->id));
        $this->sendResponse($response);
        $this->terminate();
    }

    public function actionDeleteProgram($id)
    {

        $program = $this->programRepo->find($id);
        if ($program != null) {
            $this->context->database->remove($program);
            $this->context->database->flush();
            $response = new \Nette\Application\Responses\JsonResponse(array('status' => 'ok'));
        } else {
            $response = new \Nette\Application\Responses\JsonResponse(array('status' => 'error'));
        }
        $this->sendResponse($response);
        $this->terminate();

    }

    public function actionGetBlocks()
    {
        $blocks = $this->context->database->getRepository('\SRS\Model\Program\Block')->findAll();
        $result = array();

        foreach ($blocks as $block) {
            $result[$block->id] = array('id' => $block->id,
                'name' => $block->name,
                'tools' => $block->tools,
                'room' => $block->room != null ? $block->room->name : 'Nezadána',
                'capacity' => $block->capacity,
                'duration' => $block->duration,
                'perex' => $block->perex,
                'description' => $block->description,
                'program_count' => $block->programs->count(),
                'category' => $block->category != null ? $block->category->name : 'Nezařazeno'
            );
            if (isset($block->lector) && $block->lector != null) {
                $result[$block->id]['lector'] = "{$block->lector->displayName}";
                $result[$block->id]['lector_about'] = $block->lector->about;
            } else {
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
        $seminarStartDay = $dw = date("w", strtotime($fromDate));
        $toDate = $this->dbsettings->get('seminar_to_date');

        $datediff = strtotime($toDate) - strtotime($fromDate);
        $seminarDuration = (int)floor($datediff / (60 * 60 * 24)) + 1;
        //\Nette\Diagnostics\Debugger::dump($seminarStartDay);
        $datePieces = explode('.', $fromDate);
        $calConfig['seminar_duration'] = $seminarDuration;
        $calConfig['seminar_start_day'] = $seminarStartDay;
        $calConfig['year'] = $datePieces[2];
        $calConfig['month'] = $datePieces[1] - 1; //fullcalendar je zerobased
        $calConfig['date'] = $datePieces[0];
        $calConfig['basic_block_duration'] = $this->dbsettings->get('basic_block_duration');
        if ((bool)$this->dbsettings->get('is_allowed_modify_schedule') && $this->user->isAllowed($this->resource, Permission::MANAGE_HARMONOGRAM)) {
            $calConfig['is_allowed_modify_schedule'] = true;
        } else {
            $calConfig['is_allowed_modify_schedule'] = false;
        }

        $response = new \Nette\Application\Responses\JsonResponse($calConfig);
        $this->sendResponse($response);
        $this->terminate();
    }

    /**
     * @param integer $id programID
     */
    public function actionAttend($id)
    {
        if (!$this->context->user->isLoggedIn()) { //uzivatel neni prihlasen
            $message = array('status' => 'error', 'message' => 'Uživatel není přihlášen');
        } else { //uzivatel je prihlasen
            $program = $this->programRepo->find($id);
            if ($program == null) { //ID programu neexistuje
                $message = array('status' => 'error', 'message' => 'Program s tímto id neexistuje');
            } else { // ID programu existuje

                if ($program->block == null) { // program nema prirazeny blok
                    $message = array('status' => 'error', 'message' => 'Na blok, který nemá přiřazen žádný program se nelze přihlásit.');
                } else { // program ma prirazeny blok
                    if ($program->block->capacity > $program->attendees->count()) { //program ma volne misto
                        $userRepo = $this->context->database->getRepository('\SRS\Model\User');
                        $user = $userRepo->find($this->context->user->id);
                        if ($user->hasOtherProgram($program, $this->basicBlockDuration)) {
                            $message = array('status' => 'error', 'message' => 'V tuto dobu máte přihlášený již jiný program.');
                        } else if ($user->hasSameProgram($program)) {
                            $message = array('status' => 'error', 'message' => 'Tento program máte již přihlášený.');
                        } else {
                            if (!$program->attendees->contains($user)) { // uzivatle na program jeste neni prihlasen
                                $program->attendees->add($user);
                                $this->context->database->flush();
                                $message = array('status' => 'success', 'message' => 'Program úspěšně přihlášen.');
                            } else { // uzivatel uz je na program prihlasen
                                $message = array('status' => 'error', 'message' => 'Uživatel je již přihlášen');
                            }
                        }
                    } else { // program je plny
                        $message = array('status' => 'error', 'message' => 'Kapacita programu je již plná');
                    }
                }
            }
        }
        $program->prepareForJson(null, $this->basicBlockDuration, $program->blocks);
        $message['event']['attendees_count'] = $program->attendeesCount;
        $response = new \Nette\Application\Responses\JsonResponse($message);
        $this->sendResponse($response);
        $this->terminate();
    }

    /**
     * @param integer $id programID
     */
    public function actionUnattend($id)
    {
        if (!$this->context->user->isLoggedIn()) {
            $message = array('status' => 'error', 'message' => 'Uživatel není přihlášen');
        } else {
            $program = $this->programRepo->find($id);
            if ($program == null) {
                $message = array('status' => 'error', 'message' => 'Program s tímto id neexistuje');
            } else {
                $user = $this->context->database->getRepository('\SRS\Model\User')->find($this->context->user->id);
                if ($program->attendees->contains($user)) {
                    $program->attendees->removeElement($user);
                    $this->context->database->flush();
                    $message = array('status' => 'success', 'message' => 'Program úspěšně odhlášen.');
                } else {
                    $message = array('status' => 'error', 'message' => 'Program vůbec nebyl přihlášen');
                }
            }
        }
        $program->prepareForJson(null, $this->basicBlockDuration, $program->blocks);
        $message['event']['attendees_count'] = $program->attendeesCount;
        $response = new \Nette\Application\Responses\JsonResponse($message);
        $this->sendResponse($response);
        $this->terminate();
    }
}
