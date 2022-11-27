<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\Payment\Repositories\PaymentRepository;
use App\Services\ApplicationService;
use Nette\Application\Responses\TextResponse;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující párování nespárovaných přihlášek.
 */
class PaymentPresenter extends ActionBasePresenter
{
    #[Inject]
    public PaymentRepository $paymentRepository;

    #[Inject]
    public ApplicationService $applicationService;

    public function actionPairPayments(): void
    {
        $notPairedPayments = $this->paymentRepository->findNotPairedVs();

        foreach ($notPairedPayments as $payment) {
            $this->applicationService->pairPayment($payment);
        }

        $response = new TextResponse(null);
        $this->sendResponse($response);
    }
}
