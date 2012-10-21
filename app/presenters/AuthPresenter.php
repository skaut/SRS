<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 21.10.12
 * Time: 15:51
 * To change this template use File | Settings | File Templates.
 */
use \Nette\Diagnostics\Debugger;

/**
 * Obstarava prihlasovani a odhlasovani uzivatelu
 */
class AuthPresenter extends \SRS\BasePresenter
{
    /**
     * OCEKAVAME : $_POST[skautIS_Token], $_POST[skautIS_IDRole], $_POST[skautIS_IDUnit]
     */
    public function renderLogin() {
        \Nette\Diagnostics\Debugger::dump($this->context->parameters['skautis']['appID']);
        $httpRequest = $this->context->httpRequest;
        Debugger::dump($httpRequest->getPost());

        if ($httpRequest->getPost() == null) {
            $this->redirectUrl($this->context->parameters['skautis']['url']. '/Login/?appid='.$this->context->parameters['skautis']['appID']);
        }

        $soap_client = new \SoapClient($this->context->parameters['skautis']['url']. '/JunakWebservice/UserManagement.asmx?wsdl');
        $params = array(
            'ID_Login' => $httpRequest->getPost('skautIS_Token')
        );
        $result =  $soap_client->UserDetail(array("userDetailInput" => $params))->UserDetailResult;
        Debugger::dump($result);

        $params['ID'] = $result->ID_Person;
        $soap_person = new \SoapClient($this->context->parameters['skautis']['url']. '/JunakWebservice/OrganizationUnit.asmx?wsdl');
        $result = $soap_person->PersonDetail(array("personDetailInput" => $params))->PersonDetailResult;
        Debugger::dump($result);
    }

}
