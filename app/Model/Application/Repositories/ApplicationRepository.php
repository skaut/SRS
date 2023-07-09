<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\Application;
use App\Model\Enums\ApplicationState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

use function array_map;

/**
 * Třída spravující přihlášky.
 */
class ApplicationRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Application::class);
    }

    /**
     * Vrací přihlášku podle id.
     */
    public function findById(int|null $id): Application|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací přihlášky podle id, které mají společné všechny verze přihlášky.
     *
     * @return Collection<int, Application>
     */
    public function findByApplicationId(int $id): Collection
    {
        $result = $this->getRepository()->findBy(['applicationId' => $id]);

        return new ArrayCollection($result);
    }

    /** @return Collection<int, Application> */
    public function findValid(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('validTo'));

        return $this->getRepository()->matching($criteria);
    }

    /** @throws NonUniqueResultException */
    public function findValidByVariableSymbol(string|null $variableSymbol): Application|null
    {
        $variableSymbolRegex = '^0*' . $variableSymbol . '$';

        return $this->createQueryBuilder('a')
            ->select('a')
            ->join('a.variableSymbol', 'v')
            ->where('REGEXP(v.variableSymbol, :variableSymbol) = 1')->setParameter('variableSymbol', $variableSymbolRegex)
            ->andWhere('a.validTo IS NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Uloží přihlášku.
     */
    public function save(Application $application): void
    {
        $this->em->persist($application);
        $this->em->flush();
    }

    /**
     * Odstraní přihlášku.
     */
    public function remove(Application $application): void
    {
        $this->em->remove($application);
        $this->em->flush();
    }

    /**
     * Vrací přihlášky podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, Application>
     */
    public function findApplicationsByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací id přihlášek.
     *
     * @param Collection<int, Application> $applications
     *
     * @return int[]
     */
    public function findApplicationsIds(Collection $applications): array
    {
        return array_map(static fn (Application $o) => $o->getId(), $applications->toArray());
    }

    /**
     * @param Collection<int, Application> $pairedApplications
     *
     * @return Collection<int, Application>
     */
    public function findWaitingForPaymentOrPairedApplications(Collection $pairedApplications): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('validTo'))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('state', ApplicationState::WAITING_FOR_PAYMENT),
                Criteria::expr()->in('id', $pairedApplications->map(static fn (Application $application) => $application->getId())
                    ->toArray()),
            ));

        return $this->getRepository()->matching($criteria);
    }

    /** @return string[] */
    public function getApplicationsVariableSymbolsOptions(): array
    {
        $options = [];
        foreach ($this->findValid() as $application) {
            $options[$application->getId()] = $application->getUser()->getLastName() . ' ' . $application->getUser()->getFirstName() . ' (' . $application->getVariableSymbolText() . ' - ' . $application->getFee() . ')';
        }

        return $options;
    }

    /**
     * @param Collection<int, Application> $pairedApplications
     *
     * @return string[]
     */
    public function getWaitingForPaymentOrPairedApplicationsVariableSymbolsOptions(Collection $pairedApplications): array
    {
        $options = [];
        foreach ($this->findWaitingForPaymentOrPairedApplications($pairedApplications) as $application) {
            $options[$application->getId()] = $application->getUser()->getLastName() . ' ' . $application->getUser()->getFirstName() . ' (' . $application->getVariableSymbolText() . ' - ' . $application->getFee() . ')';
        }

        return $options;
    }
}
