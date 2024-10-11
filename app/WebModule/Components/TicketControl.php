<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Role;
use App\Model\Settings\Queries\SettingDateTimeValueQuery;
use App\Model\Settings\Settings;
use App\Services\AclService;
use App\Services\QueryBus;
use App\WebModule\Presenters\WebBasePresenter;
use DateTimeImmutable;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Nette\Application\UI\Control;

use function assert;
use function strval;

/**
 * Komponenta se vstupenkou.
 */
class TicketControl extends Control
{
    public function __construct(private readonly QueryBus $queryBus, private readonly AclService $aclService)
    {
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/ticket.latte');

        $presenter = $this->getPresenter();
        assert($presenter instanceof WebBasePresenter);

        $this->template->dbUser = $presenter->getDbUser();

        $user = $presenter->getUser();

        if ($presenter->getUser()->isLoggedIn()) {
            $ticketDownloadFrom         = $this->queryBus->handle(new SettingDateTimeValueQuery(Settings::TICKETS_FROM));
            $template->ticketsAvailable = $ticketDownloadFrom !== null && $ticketDownloadFrom <= new DateTimeImmutable();

            $template->registeredAndPaid = ! $user->isInRole($this->aclService->findRoleNameBySystemName(Role::UNAPPROVED))
                && ! $user->isInRole($this->aclService->findRoleNameBySystemName(Role::NONREGISTERED))
                && $presenter->getDbUser()->hasPaidEveryApplication();

            $template->qr = $this->generateQr($this->presenter->getUser()->getId());
        }

        $template->render();
    }

    public function handleDownloadTicket(): void
    {
        $this->getPresenter()->redirect(':Export:Ticket:pdf');
    }

    private function generateQr(int $id): string
    {
        $qrCode = QrCode::create(strval($id));
        $qrCode
            ->setSize(150)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();

        $result = $writer->write($qrCode);

        return $result->getString();
    }
}
