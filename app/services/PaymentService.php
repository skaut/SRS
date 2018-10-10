<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Nette;
use FioApi;

/**
 * Služba pro správu plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentService
{
    use Nette\SmartObject;

    /** @var ApplicationService */
    private $applicationService;

    /** @var SettingsRepository */
    private $settingsRepository;


    public function __construct(ApplicationService $applicationService, SettingsRepository $settingsRepository) {
        $this->applicationService = $applicationService;
        $this->settingsRepository = $settingsRepository;
    }

    public function readFromFio() : void
    {
        $downloader = new FioApi\Downloader($this->settingsRepository->getValue(Settings::ACCOUNT_TOKEN));
        $transactionList = $downloader->downloadLast();

        foreach ($transactionList->getTransactions() as $transaction) {
            if ($transaction->getAmount() > 0) {
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($transaction->getDate()->getTimestamp());

                $this->applicationService->createPayment($dateTime,
                    $transaction->getAmount(),
                    $transaction->getVariableSymbol(),
                    $transaction->getId(),
                    $transaction->getSenderAccountNumber() . '/' . $transaction->getSenderBankCode(),
                    $transaction->getSenderBankName(), $transaction->getComment());
            }
        }
    }
}
