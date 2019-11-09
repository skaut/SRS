<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Payment\PaymentRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
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

    /** @var SettingsFacade */
    private $settingsFacade;

    /** @var PaymentRepository */
    private $paymentRepository;


    public function __construct(
        ApplicationService $applicationService,
        SettingsFacade $settingsFacade,
        PaymentRepository $paymentRepository
    ) {
        $this->applicationService = $applicationService;
        $this->settingsFacade = $settingsFacade;
        $this->paymentRepository  = $paymentRepository;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    public function downloadTransactions(\DateTime $from, ?string $token = null) : void
    {
        $token = $token ?: $this->settingsFacade->getValue(Settings::BANK_TOKEN);
        if ($token === null) {
            throw new \InvalidArgumentException('Token is not set.');
        }

        $downloader      = new FioApi\Downloader($token);
        $transactionList = $downloader->downloadSince($from);

        $this->createPayments($transactionList);
    }

    /**
     * @throws \Throwable
     */
    private function createPayments(FioApi\TransactionList $transactionList) : void
    {
        foreach ($transactionList->getTransactions() as $transaction) {
            $this->settingsFacade->getEntityManager()->transactional(function () use ($transaction) : void {
                $id = $transaction->getId();

                if ($transaction->getAmount() <= 0 || $this->paymentRepository->findByTransactionId($id) !== null) {
                    return;
                }

                $date = new \DateTime();
                $date->setTimestamp($transaction->getDate()->getTimestamp());

                $accountNumber = $transaction->getSenderAccountNumber() . '/' . $transaction->getSenderBankCode();

                $this->applicationService->createPayment(
                    $date,
                    $transaction->getAmount(),
                    $transaction->getVariableSymbol(),
                    $id,
                    $accountNumber,
                    $transaction->getSenderName(),
                    $transaction->getUserMessage()
                );
            });
        }

        $this->settingsFacade->setDateValue(Settings::BANK_DOWNLOAD_FROM, new \DateTime());
    }
}
