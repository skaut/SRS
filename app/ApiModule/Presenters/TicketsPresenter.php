<?php

declare(strict_types=1);

namespace App\ApiModule\Presenters;

use App\ApiModule\Dto\Tickets\SeminarInfo;
use App\ApiModule\Dto\Tickets\TicketInfo;
use App\Model\Application\RolesApplication;
use App\Model\Application\SubeventsApplication;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\TicketCheckRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\TicketCheck;
use App\Services\CommandBus;
use App\Services\QueryBus;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Nette\Application\AbortException;
use Nette\Http\IResponse;

use function array_key_exists;

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

    private Serializer $serializer;

    /**
     * @throws AbortException
     */
    public function startup(): void
    {
        parent::startup();

        $this->serializer = SerializerBuilder::create()->build();

        $apiToken = $this->queryBus->handle(new SettingStringValueQuery(Settings::TICKETS_API_TOKEN));
        if ($apiToken == null) {
            $this->sendErrorResponse(IResponse::S403_FORBIDDEN, 'authorization token not generated');
        }

        $headers = $this->getHttpRequest()->getHeaders();
        if (! array_key_exists('api-token', $headers)) {
            $this->sendErrorResponse(IResponse::S403_FORBIDDEN, 'no authorization token');
        }

        if ($headers['api-token'] != $apiToken) {
            $this->sendErrorResponse(IResponse::S403_FORBIDDEN, 'invalid authorization token');
        }
    }

    public function actionSeminarInfo(): void
    {
        $seminarName = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $data        = new SeminarInfo();
        $data->setName($seminarName);
        $dataArray = $this->serializer->toArray($data);
        $this->sendJson($dataArray);
    }

    public function actionCheckTicket(int $userId, int $subeventId): void
    {
        $user = $this->userRepository->findById($userId);
        if ($user == null) {
            $this->sendErrorResponse(IResponse::S404_NOT_FOUND, 'user not found');
        }

        $data = new TicketInfo();
        $data->setAttendeeName($user->getDisplayName());

        $roles     = [];
        $subevents = [];

        foreach ($user->getPaidAndFreeApplications() as $application) {
            if ($application instanceof RolesApplication) {
                foreach ($application->getRoles() as $role) {
                    $roles[] = $role->getName();
                }
            } elseif ($application instanceof SubeventsApplication) {
                foreach ($application->getSubevents() as $subevent) {
                    $subevents[] = $subevent->getName();
                }
            }
        }

        $data->setRoles($roles);
        $data->setSubevents($subevents);

        $checks = $user->getTicketChecks()->map(static fn (TicketCheck $check) => $check->getDatetime())->toArray();
        $data->setChecks($checks);

        $ticketCheck = new TicketCheck();
        $this->ticketCheckRepository->save($ticketCheck);

        $user->setAttended(true);
        $user->addTicketCheck($ticketCheck);
        $this->userRepository->save($user);

        $dataArray = $this->serializer->toArray($data);
        $this->sendJson($dataArray);
    }

    private function sendErrorResponse(int $code, string $message): void
    {
        $httpResponse = $this->getHttpResponse();
        $httpResponse->setCode($code);
        $this->sendJson($message);
    }
}
