<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use FioApi;
use Nette;

/**
 * Služba pro správu plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BankService
{
    use Nette\SmartObject;

    /** @var ApplicationService */
    private $applicationService;

    /** @var SettingsRepository */
    private $settingsRepository;


    public function __construct(ApplicationService $applicationService, SettingsRepository $settingsRepository)
    {
        $this->applicationService = $applicationService;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    public function downloadTransactions() : void
    {
        $downloader      = new FioApi\Downloader($this->settingsRepository->getValue(Settings::BANK_TOKEN));
        $transactionList = $downloader->downloadLast();

        $bankDownloadFrom = $this->settingsRepository->getDateValue(Settings::BANK_DOWNLOAD_FROM);

        $this->settingsRepository->getEntityManager()->transactional(function () use ($transactionList, $bankDownloadFrom) : void {
            foreach ($transactionList->getTransactions() as $transaction) {
                if ($transaction->getAmount() <= 0) {
                    continue;
                }

                $date = new \DateTime();
                $date->setTimestamp($transaction->getDate()->getTimestamp());

                if ($date < $bankDownloadFrom) {
                    continue;
                }

                $accountNumber = null;
                if ($transaction->getSenderAccountNumber() !== null && $transaction->getSenderBankCode() !== null) {
                    $accountNumber = $transaction->getSenderAccountNumber() . '/' . $transaction->getSenderBankCode();
                } elseif ($transaction->getSenderAccountNumber() !== null) {
                    $accountNumber = $transaction->getSenderAccountNumber();
                } elseif ($transaction->getSenderBankCode() !== null) {
                    $accountNumber = $transaction->getSenderBankCode();
                }

                $this->applicationService->createPayment(
                    $date,
                    $transaction->getAmount(),
                    $transaction->getVariableSymbol(),
                    $transaction->getId(),
                    $accountNumber,
                    $transaction->getSenderBankName(),
                    $transaction->getComment()
                );
            }
        });
    }
}
