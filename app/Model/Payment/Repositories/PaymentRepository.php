<?php

declare(strict_types=1);

namespace App\Model\Payment\Repositories;

use App\Model\Enums\PaymentState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Payment\Payment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující platby.
 */
class PaymentRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Payment::class);
    }

    /**
     * Vrací platbu podle id.
     */
    public function findById(?int $id): ?Payment
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací platbu podle id transakce.
     */
    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->getRepository()->findOneBy(['transactionId' => $transactionId]);
    }

    /**
     * @return Collection<int, Payment>
     */
    public function findNotPairedVs(): Collection
    {
        return new ArrayCollection($this->getRepository()->findBy(['state' => PaymentState::NOT_PAIRED_VS]));
    }

    /**
     * Uloží platbu.
     */
    public function save(Payment $payment): void
    {
        $this->em->persist($payment);
        $this->em->flush();
    }

    /**
     * Odstraní platbu.
     */
    public function remove(Payment $payment): void
    {
        foreach ($payment->getPairedApplications() as $pairedApplication) {
            $pairedApplication->setPayment(null);
            $this->em->persist($pairedApplication);
        }

        $this->em->remove($payment);
        $this->em->flush();
    }
}
