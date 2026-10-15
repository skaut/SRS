<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories;

use App\Model\Enums\ApplicationState;
use App\Model\User\User;
use Doctrine\ORM\QueryBuilder;

/**
 * Podmínka "na tento programový blok se uživatel může přihlásit".
 *
 * Uživatel se může přihlásit na blok, pokud blok nemá kategorii nebo uživatel má roli,
 * která se na kategorii může přihlašovat, a zároveň má schválenou platnou přihlášku na
 * podakci bloku. Pokud se programy zapisují až po zaplacení, musí být zaplacená jak
 * přihláška na podakci, tak přihláška na role.
 *
 * Záměrně jde o EXISTS poddotazy, ne o JOINy. Původní implementace joinovala
 * registerableRoles.users a subevent.applications a filtrovala uživatele až ve WHERE,
 * takže databáze nejdřív vyrobila mezivýsledek přes všechny uživatele a přihlášky akce
 * a teprve pak z něj vybírala jeden řádek. Náklady tím rostly s počtem účastníků, i když
 * výsledek na počtu účastníků nezávisí.
 */
trait RestrictsToUserAllowedBlocks
{
    /**
     * Omezí dotaz na bloky, na které se uživatel může přihlásit.
     *
     * @param string $blockAlias alias programového bloku v dotazu
     * @param bool   $paidOnly   vyžadovat zaplacené přihlášky
     */
    protected function restrictToUserAllowedBlocks(QueryBuilder $qb, string $blockAlias, User $user, bool $paidOnly): void
    {
        $qb->andWhere('(' . $blockAlias . '.category IS NULL OR EXISTS (
                SELECT 1
                FROM App\Model\Acl\Role allowedRole
                JOIN allowedRole.users allowedRoleUser
                JOIN allowedRole.registerableCategories allowedCategory
                WHERE allowedRoleUser = :allowedUser
                    AND allowedCategory = ' . $blockAlias . '.category
            ))')
            ->andWhere('EXISTS (
                SELECT 1
                FROM App\Model\Application\SubeventsApplication allowedSubeventApplication
                JOIN allowedSubeventApplication.user allowedSubeventApplicationUser
                JOIN allowedSubeventApplication.subevents allowedSubevent
                WHERE allowedSubeventApplicationUser = :allowedUser
                    AND allowedSubeventApplicationUser.approved = TRUE
                    AND allowedSubevent = ' . $blockAlias . '.subevent
                    AND allowedSubeventApplication.validTo IS NULL
                    AND allowedSubeventApplication.state IN (:allowedSubeventStates)
            )')
            ->setParameter('allowedUser', $user)
            ->setParameter(
                'allowedSubeventStates',
                $paidOnly ? ApplicationState::PAID_STATES : ApplicationState::NOT_CANCELED_STATES,
            );

        if (! $paidOnly) {
            return;
        }

        $qb->andWhere('EXISTS (
                SELECT 1
                FROM App\Model\Application\RolesApplication allowedRolesApplication
                JOIN allowedRolesApplication.roles allowedApplicationRole
                WHERE allowedRolesApplication.user = :allowedUser
                    AND allowedRolesApplication.validTo IS NULL
                    AND allowedRolesApplication.state IN (:allowedRolesStates)
            )')
            ->setParameter('allowedRolesStates', ApplicationState::PAID_STATES);
    }
}
