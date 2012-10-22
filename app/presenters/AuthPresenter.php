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
        $httpRequest = $this->context->httpRequest;

        if ($httpRequest->getPost() == null) {
            $this->redirectUrl($this->context->parameters['skautis']['url']. '/Login/?appid='.$this->context->parameters['skautis']['appID']);
        }

        //@TODO zavolat authenticator


        $skautIS = new \SRS\Model\skautIS(($this->context->parameters['skautis']['url']));
        $skautISUser = $skautIS->getUser($httpRequest->getPost('skautIS_Token'));
        $skautISPerson = $skautIS->getPerson($httpRequest->getPost('skautIS_Token'), $skautISUser->ID_Person);

    }

}
