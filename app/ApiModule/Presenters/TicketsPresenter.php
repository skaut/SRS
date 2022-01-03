<?php

declare(strict_types=1);

namespace App\ApiModule\Presenters;

use App\ApiModule\Dto\Tickets\SeminarInfo;
use App\ApiModule\Dto\Tickets\SubeventInfo;
use App\ApiModule\Dto\Tickets\TicketCheckInfo;
use App\Model\Application\RolesApplication;
use App\Model\Application\SubeventsApplication;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Queries\SubeventByIdQuery;
use App\Model\Structure\Queries\SubeventsQuery;
use App\Model\Structure\Subevent;
use App\Model\User\Commands\CheckTicket;
use App\Model\User\Queries\TicketChecksByUserAndSubeventQuery;
use App\Model\User\Queries\UserByIdQuery;
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
            $this->sendErrorResponse(IResponse::S403_FORBIDDEN, $this->translator->translate('api.tickets.token_not_generated'));
        }

        $headers = $this->getHttpRequest()->getHeaders();
        if (! array_key_exists('api-token', $headers)) {
            $this->sendErrorResponse(IResponse::S403_FORBIDDEN, $this->translator->translate('api.tickets.token_not_set'));
        }

        if ($headers['api-token'] != $apiToken) {
            $this->sendErrorResponse(IResponse::S403_FORBIDDEN, $this->translator->translate('api.tickets.token_invalid'));
        }
    }

    public function actionSeminar(): void
    {
        $seminarName = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $subevents   = $this->queryBus->handle(new SubeventsQuery())
            ->map(static fn (Subevent $subevent) => new SubeventInfo($subevent->getId(), $subevent->getName()))
            ->toArray();

        $data = new SeminarInfo();
        $data->setName($seminarName);
        $data->setSubevents($subevents);

        $dataArray = $this->serializer->toArray($data);
        $this->sendJson($dataArray);
    }

    public function actionCheckTicket(int $userId, int $subeventId): void
    {
        $user = $this->queryBus->handle(new UserByIdQuery($userId));
        if ($user == null) {
            $this->sendErrorResponse(IResponse::S404_NOT_FOUND, $this->translator->translate('api.tickets.user_not_found'));
        }

        $subevent = $this->queryBus->handle(new SubeventByIdQuery($subeventId));
        if ($subevent == null) {
            $this->sendErrorResponse(IResponse::S404_NOT_FOUND, $this->translator->translate('api.tickets.subevent_not_found'));
        }

        $roles       = [];
        $subevents   = [];
        $hasSubevent = false;

        foreach ($user->getPaidAndFreeApplications() as $application) {
            if ($application instanceof RolesApplication) {
                foreach ($application->getRoles() as $r) {
                    $roles[] = $r->getName();
                }
            } elseif ($application instanceof SubeventsApplication) {
                foreach ($application->getSubevents() as $s) {
                    $subevents[] = new SubeventInfo($s->getId(), $s->getName());
                    if ($s->getId() === $subeventId) {
                        $hasSubevent = true;
                    }
                }
            }
        }

        $checks = $this->queryBus->handle(new TicketChecksByUserAndSubeventQuery($user, $subevent))
            ->map(static fn (TicketCheck $check) => $check->getDatetime())
            ->toArray();

        $data = new TicketCheckInfo();
        $data->setAttendeeName($user->getDisplayName());
        $data->setRoles($roles);
        $data->setSubevents($subevents);
        $data->setHasSubevent($hasSubevent);
        $data->setSubeventChecks($checks);

        $this->commandBus->handle(new CheckTicket($user, $subevent));

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
