<?php

namespace App\Model\User;

use App\Model\Enums\ApplicationState;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující přihlášky podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsApplicationRepository extends EntityRepository
{
}
