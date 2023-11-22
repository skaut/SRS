<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Payment\Repositories\PaymentRepository;
use App\Model\Settings\Commands\SetSettingDateValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
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
 */
class BankService
{
    use Nette\SmartObject;

    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private ApplicationService $applicationService,
        private EntityManagerInterface $em,
        private PaymentRepository $paymentRepository,
    ) {
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function downloadTransactions(DateTimeImmutable $from, string|null $token = null): void
    {
        $token = $token ?: $this->queryBus->handle(new SettingStringValueQuery(Settings::BANK_TOKEN));
        if ($token === null) {
            throw new InvalidArgumentException('Token is not set.');
        }

        $downloader      = new Downloader($token);
        $transactionList = $downloader->downloadSince($from);

        $this->createPayments($transactionList);
    }

    /** @throws Throwable */
    private function createPayments(TransactionList $transactionList): void
    {
        foreach ($transactionList->getTransactions() as $transaction) {
            $this->em->wrapInTransaction(function () use ($transaction): void {
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
                        $transaction->getUserMessage(),
                    );
                }
            });
        }

        $this->commandBus->handle(new SetSettingDateValue(Settings::BANK_DOWNLOAD_FROM, new DateTimeImmutable()));
    }
}
