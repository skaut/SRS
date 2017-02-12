<?php

namespace App\ApiModule\Presenters;


use ApiModule\DTO\ProgramSaveDTO;
use App\ApiModule\DTO\CalendarConfigDTO;
use App\ApiModule\Services\ScheduleService;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Services\SkautIsService;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Security\AuthenticationException;
use Nette\Utils\Json;

/**
 * SchedulePresenter
 *
 * @package App\ApiModule\Presenters
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

    public function startup()
    {
        parent::startup();

        $this->serializer = SerializerBuilder::create()->build();

        if ($this->user->isLoggedIn())
            $this->scheduleService->setUser($this->user->id);
    }

    /**
     *
     */
    public function actionGetAllPrograms() {
        $data = $this->scheduleService->getAllPrograms();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * @throws AuthenticationException
     */
    public function actionGetUserAllowedPrograms() {
        if (!$this->user->isLoggedIn())
            throw new AuthenticationException('Uživatel musí být přihlášen');

        $data = $this->scheduleService->getUserAllowedPrograms();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * @param $data
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
     * @param $id
     */
    public function actionRemoveProgram($id)
    {
        $data = $this->scheduleService->removeProgram($id);

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    public function actionGetAllBlocks()
    {
        $data = $this->scheduleService->getAllBlocks();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    public function actionGetAllRooms()
    {
        $data = $this->scheduleService->getAllRooms();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * @throws AuthenticationException
     */
    public function actionGetCalendarConfig()
    {
        if (!$this->user->isLoggedIn())
            throw new AuthenticationException('Uživatel musí být přihlášen');

        $data = $this->scheduleService->getCalendarConfig();

        $json = $this->serializer->serialize($data, 'json');
        $response = new TextResponse($json);
        $this->sendResponse($response);
    }

    /**
     * @param integer $id programID
     */
    public function actionAttend($id)
    {

    }

    /**
     * @param integer $id programID
     */
    public function actionUnattend($id)
    {

    }
}