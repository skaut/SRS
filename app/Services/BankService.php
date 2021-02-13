<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Payment\Repositories\PaymentRepository;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use FioApi\Downloader;
use FioApi\TransactionList;
use InvalidArgumentException;
use Nette;
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

    private QueryBus $queryBus;

    private ApplicationService $applicationService;

    private EntityManagerInterface $em;

    private PaymentRepository $paymentRepository;

    public function __construct(
        QueryBus $queryBus,
        ApplicationService $applicationService,
        EntityManagerInterface $em,
        PaymentRepository $paymentRepository
    ) {
        $this->queryBus           = $queryBus;
        $this->applicationService = $applicationService;
        $this->em                 = $em;
        $this->paymentRepository  = $paymentRepository;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function downloadTransactions(DateTimeImmutable $from, ?string $token = null): void
    {
        $token = $token ?: $this->queryBus->handle(new SettingStringValueQuery(Settings::BANK_TOKEN));
        if ($token === null) {
            throw new InvalidArgumentException('Token is not set.');
        }

        $downloader      = new Downloader($token);
        $transactionList = $downloader->downloadSince($from);

        $this->createPayments($transactionList);
    }

    /**
     * @throws Throwable
     */
    private function createPayments(TransactionList $transactionList): void
    {
        foreach ($transactionList->getTransactions() as $transaction) {
            $this->em->transactional(function () use ($transaction): void {
                $id = $transaction->getId();

                if ($transaction->getAmount() > 0 && $this->paymentRepository->findByTransactionId($id) === null) {
                    $date = new DateTimeImmutable();
                    $date = $date->setTimestamp($transaction->getDate()->getTimestamp());

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
                }
            });
        }

        $this->settingsService->setDateValue(Settings::BANK_DOWNLOAD_FROM, new DateTimeImmutable());
    }
}
