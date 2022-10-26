<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Skaut\Skautis\Skautis;
use stdClass;
use Throwable;

use function array_filter;
use function in_array;

/**
 * Služba pro komunikaci se skautIS.
 */
class SkautIsService
{
    use Nette\SmartObject;

    private Cache $userRolesCache;

    public function __construct(private Skautis $skautIs, Storage $storage)
    {
        $this->userRolesCache = new Cache($storage, 'UserRoles');
    }

    /**
     * Vratí url přihlašovací stránky skautIS.
     */
    public function getLoginUrl(string $backlink): string
    {
        return $this->skautIs->getLoginUrl($backlink);
    }

    /**
     * Vrátí url odhlašovací stránky skautIS.
     */
    public function getLogoutUrl(): string
    {
        return $this->skautIs->getLogoutUrl();
    }

    /**
     * Vrátí stav přihlášení uživatele, každých 5 minut obnoví přihlášení.
     */
    public function isLoggedIn(): bool
    {
        $logoutTime = clone($this->skautIs->getUser()->getLogoutDate());
        $hardCheck  = $logoutTime->diff(new DateTimeImmutable())->i < 25; // pokud od posledniho obnoveni prihlaseni ubehlo 5 minut

        return $this->skautIs->getUser()->isLoggedIn($hardCheck);
    }

    /**
     * Nastaví údaje vrácené skautIS po úspěšném přihlášení.
     *
     * @param string[] $data
     */
    public function setLoginData(array $data): void
    {
        $this->skautIs->setLoginData($data);
    }

    /**
     * Vrátí skautIS role uživatele.
     *
     * @param ?string[] $allowedRoleTypes
     *
     * @return stdClass[]
     *
     * @throws Throwable
     */
    public function getUserRoles(int $userId, ?array $allowedRoleTypes = null): array
    {
        $roles = $this->userRolesCache->load($userId);

        if ($roles === null) {
            $roles = $this->skautIs->usr->UserRoleAll([
                'ID_User' => $userId,
                'IsActive' => true,
            ]);
            $this->userRolesCache->save($userId, $roles);
        }

        $rolesArray = $roles instanceof stdClass ? [] : $roles;

        if ($allowedRoleTypes !== null) {
            return array_filter($rolesArray, static fn (stdClass $r) => isset($r->Key) && in_array($r->Key, $allowedRoleTypes));
        }

        return $rolesArray;
    }

    /**
     * Vrátí id aktuální skautIS role uživatele.
     */
    public function getUserRoleId(): ?int
    {
        return $this->skautIs->getUser()->getRoleId();
    }

    /**
     * Změní skautIS roli uživatele.
     */
    public function updateUserRole(int $roleId): void
    {
        $response = $this->skautIs->usr->LoginUpdate([
            'ID' => $this->skautIs->getUser()->getLoginId(),
            'ID_UserRole' => $roleId,
        ]);
        if ($response) {
            $this->skautIs->getUser()->updateLoginData(null, $roleId, $response->ID_Unit);
        }
    }

    /**
     * Vrátí údaje o uživateli.
     */
    public function getUserDetail(): stdClass
    {
        return $this->skautIs->usr->UserDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
        ]);
    }

    /**
     * Vrátí údaje o osobě.
     */
    public function getPersonDetail(int $personId): stdClass
    {
        return $this->skautIs->org->PersonDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $personId,
        ]);
    }

    public function getPersonPhoto(int $personId, string $size): stdClass
    {
        return $this->skautIs->org->PersonPhoto([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $personId,
            'Size' => $size,
        ]);
    }

    /**
     * Aktualizuje údaje o osobě.
     */
    public function updatePersonBasic(int $personId, string $sex, DateTimeImmutable $birthday, string $firstName, string $lastName, string $nickName): void
    {
        $this->skautIs->org->PersonUpdateBasic([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $personId,
            'ID_Sex' => $sex,
            'Birthday' => $birthday->format('Y-m-d\TH:i:s'),
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'NickName' => $nickName,
        ], 'personUpdateBasicInput');
    }

    /**
     * Aktualizuje adresu osoby.
     */
    public function updatePersonAddress(int $personId, string $street, string $city, string $postcode, string $state): void
    {
        $skautISPerson = $this->getPersonDetail($personId);

        $this->skautIs->org->PersonUpdateAddress([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $personId,
            'Street' => $street,
            'City' => $city,
            'Postcode' => $postcode,
            'State' => $state,
            'PostalFirstLine' => $skautISPerson->PostalFirstLine,
            'PostalStreet' => $skautISPerson->PostalStreet,
            'PostalCity' => $skautISPerson->PostalCity,
            'PostalPostcode' => $skautISPerson->PostalPostcode,
            'PostalState' => $skautISPerson->PostalState,
        ], 'personUpdateAddressInput');
    }

    /**
     * Vrací id jednotky podle aktuální role uživatele.
     */
    public function getUnitId(): ?int
    {
        return $this->skautIs->getUser()->getUnitId();
    }

    /**
     * Vrací platné členství typu "řádné" nebo "čestné", pokud osoba žádné nemá vrací null.
     */
    public function getValidMembership(int $personId): ?stdClass
    {
        $membership = $this->skautIs->org->MembershipAllPerson([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Person' => $personId,
            'ID_MembershipType' => 'radne',
            'IsValid' => true,
        ]);

        if (empty((array) $membership)) { // todo: odstranit obe pretypovani (array) po update skautis/nette
            $membership = $this->skautIs->org->MembershipAllPerson([
                'ID_Login' => $this->skautIs->getUser()->getLoginId(),
                'ID_Person' => $personId,
                'ID_MembershipType' => 'cestne',
                'IsValid' => true,
            ]);

            if (empty((array) $membership)) {
                return null;
            }
        }

        return $membership->MembershipAllOutput;
    }

    /**
     * Vrací údaje o jednotce.
     */
    public function getUnitDetail(int $unitId): stdClass
    {
        return $this->skautIs->org->UnitDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $unitId,
        ]);
    }

    /**
     * @param ?string[] $allowedUnitTypes
     *
     * @return stdClass[]
     */
    public function getUnitAllUnit(?array $allowedUnitTypes = null): array
    {
        $units = $this->skautIs->org->UnitAllUnit([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Unit' => $this->skautIs->getUser()->getUnitId(),
        ], 'unitAllUnitInput');

        $unitsArray = $units instanceof stdClass ? [] : $units;

        if ($allowedUnitTypes !== null) {
            return array_filter($unitsArray, static fn (stdClass $r) => in_array($r->ID_UnitType, $allowedUnitTypes));
        }

        return $unitsArray;
    }

    /**
     * @return stdClass[]
     */
    public function getMembershipAll(int $unitId, ?int $minimalAge = null, ?DateTimeImmutable $date = null): array
    {
        $memberships = $this->skautIs->org->MembershipAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Unit' => $unitId,
            'ID_MembershipType' => 'radne',
            'OnlyDirectMember' => false,
        ], 'membershipAllInput');

        if ($minimalAge !== null) {
            return array_filter($memberships, static fn (stdClass $m) => $date->diff(new DateTimeImmutable($m->Birthday))->y >= $minimalAge);
        }

        return $memberships instanceof stdClass ? [] : $memberships;
    }

    /**
     * @return stdClass[]
     */
    public function getPersonContactAllParent(int $personId): array
    {
        $contacts = $this->skautIs->org->PersonContactAllParent([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Person' => $personId,
        ], 'personContactAllParentInput');

        return $contacts instanceof stdClass ? [] : $contacts;
    }
}
