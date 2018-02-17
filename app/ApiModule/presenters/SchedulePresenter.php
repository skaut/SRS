<?php

namespace App\ApiModule\Presenters;

use App\ApiModule\DTO\Schedule\ProgramSaveDTO;
use App\ApiModule\DTO\Schedule\ResponseDTO;
use App\ApiModule\Services\ScheduleService;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Nette\Application\Responses\TextResponse;


/**
 * API pro správu harmonogramu a zapisování programů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SchedulePresenter extends ApiBasePresenter
{
    /**
     * @var ScheduleService
     * @inject
     */
    public $scheduleService;

    /**
     * @var Serializer
     */
    private $serializer;


    /**
     * @throws \Nette\Application\AbortException
     */
    public function startup()
    {
        parent::startup();

        $this->serializer = SerializerBuilder::create()->build();

        if ($this->user->isLoggedIn()) {
            $this->scheduleService->setUser($this->user->id);
        } else {
            $data = new ResponseDTO();
            $data->setMessage($this->translator->translate('common.api.authentification_error'));
            $data->setStatus('danger');

            $json = $this->serializer->serialize($data, 'json');
            $response = new TextResponse($json);
            $this->sendResponse($response);
        }
    }

    /**
     * Vrací podrobnosti o všech programech pro použití v administraci harmonogramu.
     * @throws \Nette\Application\AbortException
     */
    public function actionGetProgramsAdmin()
    {
        $data = $this->scheduleService->getProgramsAdmin();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o programech, ke kterým má uživatel přístup, pro použití v kalendáři pro výběr programů.
     * @throws \Nette\Application\AbortException
     * @throws \App\Model\Settings\SettingsException
     */
    public function actionGetProgramsWeb()
    {
        $data = $this->scheduleService->getProgramsWeb();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o programových blocích.
     * @throws \Nette\Application\AbortException
     */
    public function actionGetBlocks()
    {
        $data = $this->scheduleService->getBlocks();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o místnostech.
     * @throws \Nette\Application\AbortException
     */
    public function actionGetRooms()
    {
        $data = $this->scheduleService->getRooms();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací nastavení pro FullCalendar.
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     */
    public function actionGetCalendarConfig()
    {
        $data = $this->scheduleService->getCalendarConfig();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Uloží nebo vytvoří program.
     * @param $data
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     */
    public function actionSaveProgram($data)
    {
        $programSaveDTO = $this->serializer->deserialize($data, ProgramSaveDTO::class, 'json');

        $data = $this->scheduleService->saveProgram($programSaveDTO);

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Smaže program.
     * @param $id
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     */
    public function actionRemoveProgram($id)
    {
        $data = $this->scheduleService->removeProgram($id);

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Přihlásí program uživateli.
     * @param $id
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     */
    public function actionAttendProgram($id)
    {
        $data = $this->scheduleService->attendProgram($id);

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Odhlásí program uživateli.
     * @param $id
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     */
    public function actionUnattendProgram($id)
    {
        $data = $this->scheduleService->unattendProgram($id);

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }
}
