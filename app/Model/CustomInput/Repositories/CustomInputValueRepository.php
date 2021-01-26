<?php

declare(strict_types=1);

namespace App\Model\CustomInput\Repositories;

use App\Model\CustomInput\CustomInputValue;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující hodnoty vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class CustomInputValueRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, CustomInputValue::class);
    }

    /**
     * Vrací hodnotu vlastního pole přihlášky podle id.
     */
    public function findById(?int $id): ?CustomInputValue
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Uloží hodnotu vlastního pole přihlášky.
     *
     * @throws ORMException
     */
    public function save(CustomInputValue $value): void
    {
        $this->em->persist($value);
        $this->em->flush();
    }

    /**
     * Odstraní hodnotu vlastního pole přihlášky.
     *
     * @throws ORMException
     */
    public function remove(CustomInputValue $value): void
    {
        $this->em->remove($value);
        $this->em->flush();
    }
}
