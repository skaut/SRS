<?php

declare(strict_types=1);

namespace App\Model\CustomInput\Repositories;

use App\Model\CustomInput\CustomInputValue;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující hodnoty vlastních polí přihlášky.
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
    public function findById(int|null $id): CustomInputValue|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Uloží hodnotu vlastního pole přihlášky.
     */
    public function save(CustomInputValue $value): void
    {
        $this->em->persist($value);
        $this->em->flush();
    }

    /**
     * Odstraní hodnotu vlastního pole přihlášky.
     */
    public function remove(CustomInputValue $value): void
    {
        $this->em->remove($value);
        $this->em->flush();
    }
}
