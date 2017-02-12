<?php

namespace App\ApiModule\Presenters;


use ApiModule\DTO\ProgramSaveDTO;
use App\ApiModule\DTO\CalendarConfigDTO;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use App\Presenters\BasePresenter;
use JMS\Serializer\SerializerBuilder;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Security\AuthenticationException;

class ApiPresenter extends BasePresenter
{
    /**
     * @var BlockRepository
     * @inject
     */
    public $blockRepository;

    /**
     * @var ProgramRepository
     * @inject
     */
    public $programRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepositoy;

    /**
     * @param bool $userAttendingInfo Chceme Informace o tom, zda se prihlaseny uzivatel ucastni programu?
     * @param bool $onlyAssigned
     * @param bool $userAllowed
     * @throws AuthenticationException
     */
    public function actionGetPrograms($userAttendingInfo = false, $onlyAssigned = false, $userAllowed = false)
    {
        if ($userAttendingInfo || $userAllowed) {
            if (!$this->user->isLoggedIn())
                throw new AuthenticationException('Uživatel musí být přihlášen');

            $dbuser = $this->userRepository->findById($this->user->id);
            $user = $this->user;
        } else {
            $dbuser = null;
            $user = null;
        }

        $programs = $this->programRepository->findProgramsForSchedule($user, $dbuser, $onlyAssigned, $userAllowed);

        $serializer = SerializerBuilder::create()->build();
        $json = $serializer->serialize($programs, 'json');

        $response = new TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();
    }

    /**
     * @param $data
     */
    public function actionSaveProgram($data)
    {
        $serializer = SerializerBuilder::create()->build();
        $program = $serializer->deserialize($data, ProgramSaveDTO::class, 'json');

        $this->programRepository->saveProgramFromSchedule($program);

        $response = new JsonResponse(['id' => $program->id]);
        $this->sendResponse($response);
        $this->terminate();
    }

    /**
     * @param $id
     */
    public function actionRemoveProgram($id)
    {
        $program = $this->programRepository->findById($id);

        if ($program) {
            $this->programRepository->remove($program);
            $response = new JsonResponse(['status' => 'ok']);
        } else {
            $response = new JsonResponse(['status' => 'error']);
        }

        $this->sendResponse($response);
        $this->terminate();
    }

    public function actionGetBlocks()
    {
        $blocks = $this->blockRepository->findAll();
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

        $response = new JsonResponse($result);
        $this->sendResponse($response);
        $this->terminate();
    }

    public function actionGetCalendarConfig()
    {
        $calendarConfigDTO = new CalendarConfigDTO();

        $fromDate = $this->settingsRepositoy->getDateValue('seminar_from_date');
        $toDate = $this->settingsRepositoy->getDateValue('seminar_to_date');

        $calendarConfigDTO->setSeminarStartDay($fromDate->format('w'));
        $calendarConfigDTO->setSeminarDuration($toDate->diff($fromDate)->d);
        $calendarConfigDTO->setYear($fromDate->format('Y'));
        $calendarConfigDTO->setMonth($fromDate->format('n') - 1);
        $calendarConfigDTO->setDay($fromDate->format('j'));
        $calendarConfigDTO->setBasicBlockDuration($this->settingsRepositoy->getValue('basic_block_duration'));
        $calendarConfigDTO->setModifyAllowed(
            $this->settingsRepositoy->getValue('is_allowed_modify_schedule') &&
            $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)
        );

        $response = new JsonResponse($calendarConfigDTO);
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

        $message['event']['attendees_count'] = $program->getAttendeesCount();
        $response = new JsonResponse($message);
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
        $response = new JsonResponse($message);
        $this->sendResponse($response);
        $this->terminate();
    }
}