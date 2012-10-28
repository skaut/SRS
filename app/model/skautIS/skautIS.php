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

    /** @var string */
    protected $skautISUrl;

    /** @var string */
    public $webServicesSlug = "JunakWebservice";

    /** @var string */
    public $userManagementServiceSlug = "UserManagement.asmx?wsdl";
    /** @var string */
    public $organizationUnitServiceSlug = "OrganizationUnit.asmx?wsdl";


    /**
     * @param string $skautISUrl
     */
    public function __construct($skautISUrl) {
        $this->skautISUrl = $skautISUrl;
    }


    /**
     * @return \SoapClient
     */
    protected function getUserManagementService() {
        if ($this->userManagementService == null) {
            $this->userManagementService = new \SoapClient($this->skautISUrl. '/' . $this->webServicesSlug. '/' . $this->userManagementServiceSlug);
        }
        return $this->userManagementService;
    }

    /**
     * @return \SoapClient
     */
    protected function getOrganizationUnitService() {
        if ($this->organizationUnitService == null) {
            $this->organizationUnitService = new \SoapClient($this->skautISUrl. '/' . $this->webServicesSlug. '/' . $this->organizationUnitServiceSlug);
        }
        return $this->organizationUnitService;
    }

    /**
     * @param string $token
     * @return mixed
     */
    public function getUser($token) {
        $params = array(
            'ID_Login' => $token,
        );
        $response = $this->getUserManagementService()->UserDetail(array("userDetailInput" => $params))->UserDetailResult;
        return $response;
    }

    /**
     * @param string $token skautIS Token
     * @param string $userId
     * @return mixed Person skautISu
     */
    public function getPerson($token, $personID) {
        $params = array(
            'ID_Login' => $token,
            'ID' => $personID
        );
        $response = $this->getOrganizationUnitService()->PersonDetail(array("personDetailInput" => $params))->PersonDetailResult;
        return $response;

    }

    /**
     * @param string $token skautisToken
     */
    public function refreshUserExpiration($token) {
        $params = array(
            'ID' => $token
        );
        $response = $this->getUserManagementService()->LoginUpdateRefresh(array('loginUpdateRefreshInput' => $params));
    }



}
