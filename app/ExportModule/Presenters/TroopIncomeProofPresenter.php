<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Application\Application;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Enums\TroopApplicationState;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\Troop;
use App\Services\ApplicationService;
use App\Services\QueryBus;
use App\Utils\Helpers;
use Contributte\PdfResponse\PdfResponse;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\DI\Attributes\Inject;
use Throwable;

use function assert;
use function bin2hex;
use function random_bytes;

/**
 * Presenter pro generování dokladů.
 */
class TroopIncomeProofPresenter extends ExportBasePresenter
{
    #[Inject]
    public QueryBus $queryBus;

    #[Inject]
    public ApplicationService $applicationService;

    #[Inject]
    public ApplicationRepository $applicationRepository;

    #[Inject]
    public PdfResponse $pdfResponse;

    #[Inject]
    public UserRepository $userRepository;

    #[Inject]
    public TroopRepository $troopRepository;

    /**
     * @throws ForbiddenRequestException
     */
    public function startup(): void
    {
        parent::startup();

        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }
    }

    public function actionTroop(int $id): void
    {
        $troops = new ArrayCollection();
        $troop  = $this->troopRepository->findById($id);

        if ($troop->getState() === TroopApplicationState::PAID) {
            $troops->add($troop);
        }

        $this->generateTroopIncomeProofs($troops);
    }

    /**
     * @param Collection<int, Troop> $troops
     *
     * @throws AbortException
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws NonUniqueResultException
     */
    private function generateTroopIncomeProofs(Collection $troops): void
    {
        $template = $this->createTemplate();
        assert($template instanceof Template);
        $template->setFile(__DIR__ . '/templates/TroopIncomeProof/pdf.latte');

        $template->troops            = $troops;
        $template->logo              = $this->queryBus->handle(new SettingStringValueQuery(Settings::LOGO));
        $template->seminarName       = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $template->company           = $this->queryBus->handle(new SettingStringValueQuery(Settings::COMPANY));
        $template->ico               = $this->queryBus->handle(new SettingStringValueQuery(Settings::ICO));
        $template->accountNumber     = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER));
        $template->accountant        = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNTANT));
        $template->date              = (new DateTimeImmutable())->format(Helpers::DATE_FORMAT);

        $this->pdfResponse->setTemplate($template);

        $this->pdfResponse->documentTitle = 'potvrzeni-platby';
        $this->pdfResponse->pageFormat    = 'A4';
        $this->pdfResponse->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', bin2hex(random_bytes(40)));

        $this->sendResponse($this->pdfResponse);
    }
}
