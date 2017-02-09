<?php

namespace App\Services;


use Nette;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;

class SkautIsService extends Nette\Object
{
    /**
     * @var Skautis
     */
    private $skautIS;

    public function __construct(Skautis $skautIS)
    {
        $this->skautIS = $skautIS;
    }

    public function getLoginUrl($backlink) {
        return $this->skautIS->getLoginUrl($backlink);
    }

    public function getLogoutUrl() {
        return $this->skautIS->getLogoutUrl();
    }

    public function isLoggedIn()
    {
        return $this->skautIS->getUser()->isLoggedIn(true);
    }

    public function setLoginData($data) {
        $this->skautIS->setLoginData($data);
    }

    public function getUserDetail() {
        return $this->skautIS->usr->UserDetail([
            'ID_Login' => $this->skautIS->getUser()->getLoginId()
        ]);
    }

    public function getPersonDetail($personId) {
        return $this->skautIS->org->PersonDetail([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID' => $personId
        ]);
    }

    public function updatePersonBasic($personId, $sex, $birthday, $firstName, $lastName, $nickName) {
        $this->skautIS->org->PersonUpdateBasic([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID' => $personId,
            'ID_Sex' => $sex,
            'Birthday' => $birthday->format('Y-m-d\TH:i:s'),
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'NickName' => $nickName
        ], 'personUpdateBasicInput');
    }

    public function updatePersonAddress($personId, $street, $city, $postcode, $state) {
        $skautISPerson = $this->getPersonDetail($personId);

        $this->skautIS->org->PersonUpdateAddress([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
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

    public function getUnitId() {
        return $this->skautIS->getUser()->getUnitId();
    }

    public function getUnitDetail($unitId) {
        return $this->skautIS->org->UnitDetail([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID' => $unitId
        ]);
    }

    public function getDraftEvents() {
        return $this->skautIS->event->EventGeneralAll([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID_EventGeneralState' => 'draft'
        ]);
    }

    public function getEventDetail($eventId) {
        return $this->skautIS->event->EventGeneralDetail([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID' => $eventId
        ]);
    }

    public function getEventDisplayName($eventId) {
        return $this->getEventDetail($eventId)->DisplayName;
    }

    public function isEventDraft($eventId) {
        return $this->getEventDetail($eventId)->ID_EventGeneralState == 'draft';
    }

    public function syncParticipants($eventId, $participants) {
        $skautISParticipants = $this->skautIS->event->ParticipantGeneralAll([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId
        ]);

        foreach ($skautISParticipants as $p) {
            if ($p->CanDelete)
                $this->deleteParticipant($p->ID);
        }

        foreach ($participants as $p) {
            $this->insertParticipant($p->getSkautISPersonId(), $eventId);
        }
    }

    private function deleteParticipant($participantId)
    {
        $this->skautIS->event->ParticipantGeneralDelete([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID' => $participantId,
            'DeletePerson' => false
        ]);
    }

    private function insertParticipant($participantId, $eventId)
    {
        $this->skautIS->event->ParticipantGeneralInsert([
            'ID_Login' => $this->skautIS->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
            'ID_Person' => $participantId
        ]);
    }

    public function getEventsOptions()
    {
        $options = [];
        try {
            foreach ($this->getDraftEvents() as $e)
                $options[$e->ID] = $e->DisplayName;
        } catch (WsdlException $ex) { }
        return $options;
    }
}