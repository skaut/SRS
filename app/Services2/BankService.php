<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Payment\PaymentRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use DateTime;
use FioApi;
use InvalidArgumentException;
use Nette;
use Nettrine\ORM\EntityManagerDecorator;
use Throwable;

/**
 * Služba pro správu plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class BankService
{
    use Nette\SmartObject;

    /** @var ApplicationService */
    private $applicationService;

    /** @var EntityManagerDecorator */
    private $em;

    /** @var SettingsService */
    private $settingsService;

    /** @var PaymentRepository */
    private $paymentRepository;

    public function __construct(
        ApplicationService $applicationService,
        EntityManagerDecorator $em,
        SettingsService $settingsService,
        PaymentRepository $paymentRepository
    ) {
        $this->applicationService = $applicationService;
        $this->em                 = $em;
        $this->settingsService    = $settingsService;
        $this->paymentRepository  = $paymentRepository;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function downloadTransactions(DateTime $from, ?string $token = null) : void
    {
        $token = $token ?: $this->settingsService->getValue(Settings::BANK_TOKEN);
        if ($token === null) {
            throw new InvalidArgumentException('Token is not set.');
        }

        $downloader      = new FioApi\Downloader($token);
        $transactionList = $downloader->downloadSince($from);

        $this->createPayments($transactionList);
    }

    /**
     * @throws Throwable
     */
    private function createPayments(FioApi\TransactionList $transactionList) : void
    {
        foreach ($transactionList->getTransactions() as $transaction) {
            $this->em->transactional(function () use ($transaction) : void {
                $id = $transaction->getId();

                if ($transaction->getAmount() <= 0 || $this->paymentRepository->findByTransactionId($id) !== null) {
                    return;
                }

                $date = new DateTime();
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

        $this->settingsService->setDateValue(Settings::BANK_DOWNLOAD_FROM, new DateTime());
    }
}
