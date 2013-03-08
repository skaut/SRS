<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 22.10.12
 * Time: 11:40
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Model;

/**
 * Zaobaluje praci se skautIS WSDL pro human readable pouzivani a odprostuje od jinak nezbytne znalosti struktury Services skautISu
 */
class skautIS extends \Nette\Object
{
    /** @var \SoapClient */
    protected $userManagementService;

    /** @var \SoapClient */
    protected $organizationUnitService;

    /** @var \SoapClient */
    protected $applicationManagementService;

    /** @var \SoapClient */
    protected $eventsService;

    /** @var string */
    protected $skautISUrl;

    /** @var string */
    protected $skautISAppID;

    /** @var string */
    public $webServicesSlug = "JunakWebservice";

    /** @var string */
    public $userManagementServiceSlug = "UserManagement.asmx?wsdl";

    /** @var string */
    public $organizationUnitServiceSlug = "OrganizationUnit.asmx?wsdl";

    /** @var string */
    public $applicationManagementServiceSlug = "ApplicationManagement.asmx?wsdl";

    public $eventsServiceSlug = "Events.asmx?wsdl";


    /**
     * @param string $skautISUrl
     */
    public function __construct($skautISUrl, $skautISAppID)
    {
        $this->skautISUrl = $skautISUrl;
        $this->skautISAppID = $skautISAppID;

    }


    /**
     * @return \SoapClient
     */
    protected function getUserManagementService()
    {
        if ($this->userManagementService == null) {
            $this->userManagementService = new \SoapClient($this->skautISUrl. '/' . $this->webServicesSlug. '/' . $this->userManagementServiceSlug);
        }
        return $this->userManagementService;
    }

    protected function getEventsService()
    {
        if ($this->eventsService == null) {
            $this->eventsService = new \SoapClient($this->skautISUrl. '/' . $this->webServicesSlug. '/' . $this->eventsServiceSlug);
        }
        return $this->eventsService;
    }

    /**
     * @return \SoapClient
     */
    protected function getOrganizationUnitService()
    {
        if ($this->organizationUnitService == null) {
            $this->organizationUnitService = new \SoapClient($this->skautISUrl. '/' . $this->webServicesSlug. '/' . $this->organizationUnitServiceSlug, array('ID_Application' => $this->skautISAppID));
        }
        return $this->organizationUnitService;
    }

    protected function getApplicationManagementService()
    {
        if ($this->applicationManagementService == null) {
            $this->applicationManagementService = new \SoapClient($this->skautISUrl. '/' . $this->webServicesSlug. '/' . $this->applicationManagementServiceSlug);
        }
        //\Nette\Diagnostics\Debugger::dump($this->applicationManagementService);

        return $this->applicationManagementService;
    }

    /**
     * @param string $token
     * @return mixed
     */
    public function getUser($token)
    {
        $params = array(
            'ID_Login' => $token,
        );
        $response = $this->getUserManagementService()->UserDetail(array("userDetailInput" => $params))->UserDetailResult;
        return $response;
    }

    /**
     * @param string $token skautIS Token
     * @param int $personID
     * @return mixed Person skautISu
     */
    public function getPerson($token, $personID)
    {
        $params = array(
            'ID_Login' => $token,
            'ID' => $personID
        );
       // $response = $this->getOrganizationUnitService()->__soapCall('PersonDetail',array('PersonDetail' => array('personDetailInput' => $params)) )->PersonDetailResult;
        $response = $this->getOrganizationUnitService()->PersonDetail(array("personDetailInput" => $params))->PersonDetailResult;
        return $response;

    }

    //nepouziva se
    public function getMembership($token, $membershipID)
    {
        $params = array(
            'ID_Login' => $token,
            'ID' => $membershipID
        );
        $response = $this->getOrganizationUnitService()->MembershipDetail(array('membershipDetailInput' => $params))->MembershipDetailResult;
        return $response;
    }

    /**
     * Vola anonymni funkci ve skautIS pro overeni. Ve skautIS v soucasne dobe neni funkce ktera by slouzila primo pro overeni platnosti id
     * @param $appId
     * @return array
     */
    public function checkAppId($appId) {
        $params = array(
            'ID_Application' => $appId
        );
        try {
            $response = $this->getOrganizationUnitService()->UnitAllRegistryBasic(array('unitAllRegistryBasicInput' => $params))->UnitAllRegistryBasicResult;
            $result = array('success' => true, 'message' => '');
        }
        catch (\SoapFault $e) {
            $result = array('success' => false, 'message' => $e->getMessage());
        }

        return $result;
    }


    public function updatePerson($person, $token)
    {
        $person->ID_Login = $token;

        $this->getOrganizationUnitService()->PersonUpdateBasic(array('personUpdateBasicInput' => $person));
        $this->getOrganizationUnitService()->PersonUpdateAddress(array('personUpdateAddressInput' => $person));
    }

    public function getEvents($token)
    {
        $params = array (
          'ID_Login' => $token,
          //'ID_Person' => $skautISPersonId
        );

        $response = $this->getEventsService()->EventGeneralAll(array('eventGeneralAllInput' => $params))->EventGeneralAllResult;
        if (isset($response->EventGeneralAllOutput)) $response = $response->EventGeneralAllOutput;
        return $response;
    }

    public function syncParticipants($token, $skautISEventId, $users)
    {
        $params = array(
            'ID_Login' => $token,
            'ID_EventGeneral' => $skautISEventId
        );
        $skautISParticipants = $this->getEventsService()->ParticipantGeneralAll(array('participantGeneralAllInput' => $params))->ParticipantGeneralAllResult;
        if (isset($skautISParticipants->ParticipantGeneralAllOutputt)) $skautISParticipants = $skautISParticipants->ParticipantGeneralAllOutput;
        foreach($skautISParticipants as $p) {
            if ($p->CanDelete == true) {
                $this->deleteParticipant($token, $p->ID, $skautISEventId);
            }
        }

        foreach ($users as $user) {
            $this->insertParticipant($token, $user->skautISPersonId, $skautISEventId);
        }
    }

    protected function deleteParticipant($token, $skautISParticipantId, $skautISEventId)
    {
        $params = array(
          'ID_Login' => $token,
          'ID' => $skautISParticipantId,
          'DeletePerson' => false
        );
        $this->getEventsService()->ParticipantGeneralDelete(array('participantGeneralDeleteInput' => $params));
    }

    protected function insertParticipant($token, $skautISPersonId, $skautISEventId)
    {
        $params = array(
            'ID_Login' => $token,
            'ID_EventGeneral' => $skautISEventId,
            'ID_Person' => $skautISPersonId
        );
        $this->getEventsService()->ParticipantGeneralInsert(array('participantGeneralInsertInput' => $params));
    }


    /**
     * @param string $token skautisToken
     * @throws \SoapFault pokud vlozime neplatny token
     */
    public function refreshUserExpiration($token)
    {
        $params = array(
            'ID' => $token
        );
        $this->getUserManagementService()->LoginUpdateRefresh(array('loginUpdateRefreshInput' => $params));
    }



}
