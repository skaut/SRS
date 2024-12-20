<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Skaut\Skautis\Skautis;
use Skaut\Skautis\Wsdl\AuthenticationException;
use stdClass;
use Throwable;

/**
 * Služba pro komunikaci se skautIS.
 */
class SkautIsService
{
    use Nette\SmartObject;

    private Cache $userRolesCache;

    public function __construct(private readonly Skautis $skautIs, Storage $storage)
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
     * Vrátí stav přihlášení uživatele, každých 15 minut obnoví přihlášení.
     */
    public function isLoggedIn(): bool
    {
        $logoutTime = clone($this->skautIs->getUser()->getLogoutDate());
        $hardCheck  = $logoutTime->diff(new DateTimeImmutable())->i < 15; // pokud od posledniho obnoveni prihlaseni ubehlo 15 minut

        try {
            return $this->skautIs->getUser()->isLoggedIn($hardCheck);
        } catch (AuthenticationException) {
            return false;
        }
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
     * @return stdClass[]
     *
     * @throws Throwable
     */
    public function getUserRoles(int $userId): array
    {
        $roles = $this->userRolesCache->load($userId);

        if ($roles === null) {
            $roles = $this->skautIs->usr->UserRoleAll([
                'ID_User' => $userId,
                'IsActive' => true,
            ]);
            $this->userRolesCache->save($userId, $roles);
        }

        return $roles instanceof stdClass ? [] : $roles;
    }

    /**
     * Vrátí id aktuální skautIS role uživatele.
     */
    public function getUserRoleId(): int|null
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

    public function getPersonContact(int $personId, string $contactType): stdClass|null
    {
        $contact = $this->skautIs->org->PersonContactAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Person' => $personId,
            'ID_ContactType' => $contactType,
        ], 'personContactAllInput');

        if (empty($contact)) {
            return null;
        }

        return $contact[0];
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
    public function getUnitId(): int|null
    {
        return $this->skautIs->getUser()->getUnitId();
    }

    /**
     * Vrací platné členství typu "řádné" nebo "čestné", pokud osoba žádné nemá vrací null.
     */
    public function getValidMembership(int $personId): stdClass|null
    {
        $memberships = $this->skautIs->org->MembershipAllPerson([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Person' => $personId,
            'IsValid' => true,
        ]);

        if (empty($memberships)) {
            return null;
        }

        $membershipsArray = $memberships->MembershipAllOutput instanceof stdClass ? [$memberships->MembershipAllOutput] : $memberships->MembershipAllOutput;

        foreach ($membershipsArray as $membership) {
            if ($membership->ID_MembershipType === 'radne') {
                return $membership;
            }
        }

        foreach ($membershipsArray as $membership) {
            if ($membership->ID_MembershipType === 'cestne') {
                return $membership;
            }
        }

        return null;
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
}
