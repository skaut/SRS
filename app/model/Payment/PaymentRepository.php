<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;
use function array_map;

/**
 * Třída spravující platby.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
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
     * Uloží platbu.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Payment $payment) : void
    {
        $this->_em->persist($payment);
        $this->_em->flush();
    }

    /**
     * Odstraní platbu.
     * @throws ORMException
     * @throws OptimisticLockException
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
