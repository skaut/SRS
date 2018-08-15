<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Skautis\Skautis;

/**
 * Služba pro komunikaci se skautIS.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsService
{
    use Nette\SmartObject;

    /** @var Skautis */
    private $skautIs;

    /** @var Cache */
    private $userRolesCache;


    public function __construct(Skautis $skautIS, IStorage $storage)
    {
        $this->skautIs        = $skautIS;
        $this->userRolesCache = new Cache($storage, 'UserRoles');
    }

    /**
     * Vratí url přihlašovací stránky skautIS.
     * @param $backlink
     */
    public function getLoginUrl($backlink) : string
    {
        return $this->skautIs->getLoginUrl($backlink);
    }

    /**
     * Vrátí url odhlašovací stránky skautIS.
     */
    public function getLogoutUrl() : string
    {
        return $this->skautIs->getLogoutUrl();
    }

    /**
     * Vrátí stav přihlášení uživatele, každých 5 minut obnoví přihlášení.
     */
    public function isLoggedIn() : bool
    {
        $logoutTime = clone($this->skautIs->getUser()->getLogoutDate());
        $hardCheck  = $logoutTime->diff(new \DateTime())->i < 25; //pokud od posledniho obnoveni prihlaseni ubehlo 5 minut
        return $this->skautIs->getUser()->isLoggedIn($hardCheck);
    }

    /**
     * Nastaví údaje vrácené skautIS po úspěšném přihlášení.
     * @param $data
     */
    public function setLoginData($data) : void
    {
        $this->skautIs->setLoginData($data);
    }

    /**
     * Vrátí skautIS role uživatele.
     * @param $userId
     * @return mixed
     * @throws \Throwable
     */
    public function getUserRoles($userId)
    {
        $roles = $this->userRolesCache->load($userId);

        if ($roles === null) {
            $roles = $this->skautIs->usr->UserRoleAll([
                'ID_User' => $userId,
                'IsActive' => true
            ]);
            $this->userRolesCache->save($userId, $roles);
        }

        return $roles;
    }

    /**
     * Vrátí id aktuální skautIS role uživatele.
     */
    public function getUserRoleId() : ?int
    {
        return $this->skautIs->getUser()->getRoleId();
    }

    /**
     * Změní skautIS roli uživatele.
     */
    public function updateUserRole(int $roleId) : void
    {
        $response = $this->skautIs->usr->LoginUpdate([
            'ID' => $this->skautIs->getUser()->getLoginId(),
            'ID_UserRole' => $roleId
        ]);
        if (! $response) {
            return;
        }

        $this->skautIs->getUser()->updateLoginData(null, $roleId, $response->ID_Unit);
    }

    /**
     * Vrátí údaje o uživateli.
     * @return mixed
     */
    public function getUserDetail()
    {
        return $this->skautIs->usr->UserDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId()
        ]);
    }

    /**
     * Vrátí údaje o osobě.
     * @param $personId
     * @return mixed
     */
    public function getPersonDetail($personId)
    {
        return $this->skautIs->org->PersonDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $personId
        ]);
    }

    public function getPersonPhoto($personId, $size)
    {
        return $this->skautIs->org->PersonPhoto([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $personId,
            'Size' => $size
        ]);
    }

    /**
     * Aktualizuje údaje o osobě.
     * @param $personId
     * @param $sex
     * @param $birthday
     * @param $firstName
     * @param $lastName
     * @param $nickName
     */
    public function updatePersonBasic($personId, $sex, $birthday, $firstName, $lastName, $nickName) : void
    {
        $this->skautIs->org->PersonUpdateBasic([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $personId,
            'ID_Sex' => $sex,
            'Birthday' => $birthday->format('Y-m-d\TH:i:s'),
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'NickName' => $nickName
        ], 'personUpdateBasicInput');
    }

    /**
     * Aktualizuje adresu osoby.
     * @param $personId
     * @param $street
     * @param $city
     * @param $postcode
     * @param $state
     */
    public function updatePersonAddress($personId, $street, $city, $postcode, $state) : void
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
            'PostalState' => $skautISPerson->PostalState
        ], 'personUpdateAddressInput');
    }

    /**
     * Vrací id jednotky podle aktuální role uživatele.
     */
    public function getUnitId() : ?int
    {
        return $this->skautIs->getUser()->getUnitId();
    }

    /**
     * Vrací platné členství typu "řádné" nebo "čestné", pokud osoba žádné nemá vrací null.
     * @param $personId
     */
    public function getValidMembership($personId) : ?\stdClass
    {
        $membership = $this->skautIs->org->MembershipAllPerson([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Person' => $personId,
            'ID_MembershipType' => 'radne',
            'IsValid' => true
        ]);

        if ($membership === new \stdClass()) {
            $membership = $this->skautIs->org->MembershipAllPerson([
                'ID_Login' => $this->skautIs->getUser()->getLoginId(),
                'ID_Person' => $personId,
                'ID_MembershipType' => 'cestne',
                'IsValid' => true
            ]);

            if ($membership === new \stdClass()) {
                return null;
            }
        }

        return $membership->MembershipAllOutput;
    }

    /**
     * Vrací údaje o jednotce.
     * @param $unitId
     * @return mixed
     */
    public function getUnitDetail($unitId)
    {
        return $this->skautIs->org->UnitDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $unitId
        ]);
    }
}
