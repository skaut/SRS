<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující platby.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PaymentRepository extends EntityRepository
{
    /**
     * Vrací platbu podle id.
     */
    public function findById(?int $id) : ?Payment
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací platbu podle id transakce.
     */
    public function findByTransactionId(string $transactionId) : ?Payment
    {
        return $this->findOneBy(['transactionId' => $transactionId]);
    }

    /**
     * Uloží platbu.
     *
     * @throws ORMException
     */
    public function save(Payment $payment) : void
    {
        $this->_em->persist($payment);
        $this->_em->flush();
    }

    /**
     * Odstraní platbu.
     *
     * @throws ORMException
     */
    public function remove(Payment $payment) : void
    {
        foreach ($payment->getPairedApplications() as $pairedApplication) {
            $pairedApplication->setPayment(null);
            $this->_em->persist($pairedApplication);
        }

        $this->_em->remove($payment);
        $this->_em->flush();
    }
}
