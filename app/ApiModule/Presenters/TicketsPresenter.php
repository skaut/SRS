<?php

namespace App\ApiModule\Presenters;

use App\ApiModule\Dto\Tickets\ConnectionDto;
use App\ApiModule\Dto\Tickets\TicketDto;
use App\ApiModule\Services\ApiException;
use App\Model\Acl\Role;
use App\Model\Application\Application;
use App\Model\Application\RolesApplication;
use App\Model\Application\SubeventsApplication;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\TicketCheckRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\TicketCheck;
use App\Services\CommandBus;
use App\Services\QueryBus;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\JsonResponse;
use Tracy\Debugger;

/**
 * API pro kontrolu vstupenek.
 */
class TicketsPresenter extends ApiBasePresenter
{
    /** @inject */
    public CommandBus $commandBus;

    /** @inject */
    public QueryBus $queryBus;

    /** @inject */
    public UserRepository $userRepository;

    /** @inject */
    public TicketCheckRepository $ticketCheckRepository;

    private SerializerInterface $serializer;

    /**
     * @throws BadRequestException
     */
    public function startup(): void
    {
        parent::startup();

        $this->serializer = SerializerBuilder::create()->build();

        $apiToken = $this->queryBus->handle(new SettingStringValueQuery(Settings::TICKETS_API_TOKEN));
        if ($apiToken == null) {
            throw new BadRequestException("API access token not generated.", 403);
        }

        $headers = $this->getHttpRequest()->getHeaders();
        if (!array_key_exists('authorization', $headers)) {
            throw new BadRequestException("No authorization token.", 403);
        }
        if ($headers['authorization'] != 'Bearer ' . $apiToken) {
            throw new BadRequestException("Invalid authorization token.", 403);
        }
    }

    public function actionConnect(): void
    {
        $seminarName = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $data = new ConnectionDto();
        $data->setSeminarName($seminarName);
        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }

    public function actionCheckTicket(int $id): void
    {
        $user = $this->userRepository->findById($id);
        if ($user == null) {
            throw new ApiException("User with id ID doesn't exist.");
        }

        $data = new TicketDto();
        $data->setAttendeeName($user->getDisplayName());

        $roles = [];
        $subevents = [];

        $user->getPaidAndFreeApplications()->forAll(function (Application $application) {
            if ($application instanceof RolesApplication) {
                $application->getRoles()->forAll(function (Role $role) {
                    $roles[] = $role->getName();
                });
            } else if ($application instanceof SubeventsApplication) {
                $application->getSubevents()->forAll(function (Subevent $subevent) {
                    $subevent[] = $subevent->getName();
                });
            }
        });

        $data->setRoles($roles);
        $data->setSubevents($subevents);

        $checks = $user->getTicketChecks()->map(fn(TicketCheck $check) => $check->getDatetime())->toArray();
        $data->setChecks($checks);

        $ticketCheck = new TicketCheck();
        $this->ticketCheckRepository->save($ticketCheck);

        $user->setAttended(true);
        $user->addTicketCheck($ticketCheck);
        $this->userRepository->save($user);

        $json     = $this->serializer->serialize($data, 'json');
        $response = new JsonResponse($json);
        $this->sendResponse($response);
    }
}