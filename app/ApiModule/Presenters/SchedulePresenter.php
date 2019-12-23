<?php

declare(strict_types=1);

namespace App\ApiModule\Presenters;

use App\ApiModule\Dto\Schedule\ProgramSaveDto;
use App\ApiModule\Dto\Schedule\ResponseDto;
use App\ApiModule\Services\ScheduleService;
use App\Model\Settings\SettingsException;
use Exception;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Nette\Application\AbortException;
use Nette\Application\Responses\TextResponse;
use Throwable;

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

    /** @var SerializerInterface */
    private $serializer;

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->serializer = SerializerBuilder::create()->build();

        if ($this->user->isLoggedIn()) {
            $this->scheduleService->setUser($this->user->id);
        } else {
            $data = new ResponseDto();
            $data->setMessage($this->translator->translate('common.api.authentification_error'));
            $data->setStatus('danger');

            $json     = $this->serializer->serialize($data, 'json');
            $response = new TextResponse($json);
            $this->sendResponse($response);
        }
    }

    /**
     * Vrací podrobnosti o všech programech pro použití v administraci harmonogramu.
     *
     * @throws Exception
     * @throws AbortException
     */
    public function actionGetProgramsAdmin() : void
    {
        $data = $this->scheduleService->getProgramsAdmin();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o programech, ke kterým má uživatel přístup, pro použití v kalendáři pro výběr programů.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionGetProgramsWeb() : void
    {
        $data = $this->scheduleService->getProgramsWeb();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o programových blocích.
     *
     * @throws AbortException
     */
    public function actionGetBlocks() : void
    {
        $data = $this->scheduleService->getBlocks();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o místnostech.
     *
     * @throws AbortException
     */
    public function actionGetRooms() : void
    {
        $data = $this->scheduleService->getRooms();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací nastavení pro FullCalendar.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionGetCalendarConfig() : void
    {
        $data = $this->scheduleService->getCalendarConfig();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Uloží nebo vytvoří program.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionSaveProgram(string $data) : void
    {
        /** @var ProgramSaveDto $programSaveDto */
        $programSaveDto = $this->serializer->deserialize($data, ProgramSaveDto::class, 'json');

        $data = $this->scheduleService->saveProgram($programSaveDto);

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Smaže program.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionRemoveProgram(int $id) : void
    {
        $data = $this->scheduleService->removeProgram($id);

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Přihlásí program uživateli.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionAttendProgram(int $id) : void
    {
        $data = $this->scheduleService->attendProgram($id);

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Odhlásí program uživateli.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionUnattendProgram(int $id) : void
    {
        $data = $this->scheduleService->unattendProgram($id);

        $json     = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }
}
