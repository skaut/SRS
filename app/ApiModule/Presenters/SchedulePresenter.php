<?php

declare(strict_types=1);

namespace App\ApiModule\Presenters;

use App\ApiModule\Dto\Schedule\ProgramSaveDto;
use App\ApiModule\Dto\Schedule\ResponseDto;
use App\ApiModule\Services\ApiException;
use App\ApiModule\Services\ScheduleService;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use Exception;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Nette\Application\AbortException;
use Nette\Application\Responses\JsonResponse;
use Throwable;

use function assert;
use function file_get_contents;

/**
 * API pro správu harmonogramu a zapisování programů.
 */
class SchedulePresenter extends ApiBasePresenter
{
    /** @inject */
    public ScheduleService $scheduleService;

    private SerializerInterface $serializer;

    /**
     * @throws AbortException
     */
    public function startup(): void
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
            $response = new JsonResponse($json);
            $this->sendResponse($response);
        }
    }

    /**
     * Vrací podrobnosti o všech programech pro použití v administraci harmonogramu.
     *
     * @throws Exception
     * @throws AbortException
     */
    public function actionGetProgramsAdmin(): void
    {
        $data = $this->scheduleService->getProgramsAdmin();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o programech, ke kterým má uživatel přístup, pro použití v kalendáři pro výběr programů.
     *
     * @throws SettingsItemNotFoundException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionGetProgramsWeb(): void
    {
        $data = $this->scheduleService->getProgramsWeb();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o programových blocích.
     *
     * @throws AbortException
     */
    public function actionGetBlocks(): void
    {
        $data = $this->scheduleService->getBlocks();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací podrobnosti o místnostech.
     *
     * @throws AbortException
     */
    public function actionGetRooms(): void
    {
        $data = $this->scheduleService->getRooms();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Vrací nastavení pro FullCalendar.
     *
     * @throws SettingsItemNotFoundException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionGetCalendarConfig(): void
    {
        $data = $this->scheduleService->getCalendarConfig();

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Uloží nebo vytvoří program.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionSaveProgram(): void
    {
        $programSaveDto = $this->serializer->deserialize(file_get_contents('php://input'), ProgramSaveDto::class, 'json');
        assert($programSaveDto instanceof ProgramSaveDto);

        try {
            $data = $this->scheduleService->saveProgram($programSaveDto);
        } catch (ApiException $e) {
            $this->getHttpResponse()->setCode(400);
            $data = new ResponseDto();
            $data->setMessage($e->getMessage());
            $data->setStatus('danger');
        }

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Smaže program.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionRemoveProgram(int $id): void
    {
        try {
            $data = $this->scheduleService->removeProgram($id);
        } catch (ApiException $e) {
            $this->getHttpResponse()->setCode(400);
            $data = new ResponseDto();
            $data->setMessage($e->getMessage());
            $data->setStatus('danger');
        }

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Přihlásí program uživateli.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionAttendProgram(int $id): void
    {
        try {
            $data = $this->scheduleService->attendProgram($id);
        } catch (ApiException $e) {
            $this->getHttpResponse()->setCode(400);
            $data = new ResponseDto();
            $data->setMessage($e->getMessage());
            $data->setStatus('danger');
        }

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    /**
     * Odhlásí program uživateli.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionUnattendProgram(int $id): void
    {
        try {
            $data = $this->scheduleService->unattendProgram($id);
        } catch (ApiException $e) {
            $this->getHttpResponse()->setCode(400);
            $data = new ResponseDto();
            $data->setMessage($e->getMessage());
            $data->setStatus('danger');
        }

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }
}
