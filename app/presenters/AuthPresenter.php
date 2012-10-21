<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 21.10.12
 * Time: 15:51
 * To change this template use File | Settings | File Templates.
 */
use \Nette\Diagnostics\Debugger;
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
    }

}
