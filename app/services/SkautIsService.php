<?php

namespace App\Services;

use Nette;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;


/**
 * Služba pro komunikaci se skautIS.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsService extends Nette\Object
{
    /** @var Skautis */
    private $skautIs;


    /**
     * SkautIsService constructor.
     * @param Skautis $skautIS
     */
    public function __construct(Skautis $skautIS)
    {
        $this->skautIs = $skautIS;
    }

    /**
     * Vratí url přihlašovací stránky skautIS.
     * @param $backlink
     * @return string
     */
    public function getLoginUrl($backlink)
    {
        return $this->skautIs->getLoginUrl($backlink);
    }

    /**
     * Vrátí url odhlašovací stránky skautIS.
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->skautIs->getLogoutUrl();
    }

    /**
     * Vrátí stav přihlášení uživatele, každých 5 minut obnoví přihlášení.
     * @return bool
     */
    public function isLoggedIn()
    {
        $logoutTime = clone($this->skautIs->getUser()->getLogoutDate());
        $hardCheck = $logoutTime->diff(new \DateTime())->i < 25; //pokud od posledniho obnoveni prihlaseni ubehlo 5 minut
        return $this->skautIs->getUser()->isLoggedIn($hardCheck);
    }

    /**
     * Nastaví údaje vrácené skautIS po úspěšném přihlášení.
     * @param $data
     */
    public function setLoginData($data)
    {
        $this->skautIs->setLoginData($data);
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
    public function updatePersonBasic($personId, $sex, $birthday, $firstName, $lastName, $nickName)
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
    public function updatePersonAddress($personId, $street, $city, $postcode, $state)
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
     * @return int|null
     */
    public function getUnitId()
    {
        return $this->skautIs->getUser()->getUnitId();
    }

    /**
     * Vrací platné členství typu "řádné" nebo "čestné", pokud osoba žádné nemá vrací null.
     * @param $personId
     * @return \stdClass|null
     */
    public function getValidMembership($personId)
    {
        $membership = $this->skautIs->org->MembershipAllPerson([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_Person' => $personId,
            'ID_MembershipType' => 'radne',
            'IsValid' => TRUE
        ]);

        if ($membership == new \stdClass()) {
            $membership = $this->skautIs->org->MembershipAllPerson([
                'ID_Login' => $this->skautIs->getUser()->getLoginId(),
                'ID_Person' => $personId,
                'ID_MembershipType' => 'cestne',
                'IsValid' => TRUE
            ]);

            if ($membership == new \stdClass())
                return NULL;
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

    /**
     * Vrací seznam neuzavřených dalších akcí.
     * @return mixed
     */
    public function getDraftEvents()
    {
        return $this->skautIs->event->EventGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneralState' => 'draft'
        ]);
    }

    /**
     * Vrací údaje o další akci.
     * @param $eventId
     * @return mixed
     */
    public function getEventDetail($eventId)
    {
        return $this->skautIs->event->EventGeneralDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId
        ]);
    }

    /**
     * Vrací název další akce.
     * @param $eventId
     * @return mixed
     */
    public function getEventDisplayName($eventId)
    {
        return $this->getEventDetail($eventId)->DisplayName;
    }

    /**
     * Vrací true, pokud je akce neuzavřená.
     * @param $eventId
     * @return bool
     */
    public function isEventDraft($eventId)
    {
        return $this->getEventDetail($eventId)->ID_EventGeneralState == 'draft';
    }

    /**
     * Synchronizuje účastníky.
     * @param $eventId
     * @param $participants
     */
    public function syncParticipants($eventId, $participants)
    {
        $skautISParticipants = $this->skautIs->event->ParticipantGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId
        ]);

        foreach ($skautISParticipants as $p) {
            if ($p->CanDelete)
                $this->deleteParticipant($p->ID);
        }

        foreach ($participants as $p) {
            if (!$p->isExternal())
                $this->insertParticipant($p->getSkautISPersonId(), $eventId);
        }
    }

    /**
     * Odstraňuje účastníka ze skautIS.
     * @param $participantId
     */
    private function deleteParticipant($participantId)
    {
        $this->skautIs->event->ParticipantGeneralDelete([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $participantId,
            'DeletePerson' => FALSE
        ]);
    }

    /**
     * Přidává účastníka do skautIS.
     * @param $participantId
     * @param $eventId
     */
    private function insertParticipant($participantId, $eventId)
    {
        $this->skautIs->event->ParticipantGeneralInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
            'ID_Person' => $participantId
        ]);
    }

    /**
     * Vrací seznam neuzavřených událostí pro select.
     * @return array
     */
    public function getEventsOptions()
    {
        $options = [];
        try {
            foreach ($this->getDraftEvents() as $e)
                $options[$e->ID] = $e->DisplayName;
        } catch (WsdlException $ex) {
        }
        return $options;
    }
}
