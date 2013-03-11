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
     * @param string $backlink Ve tvaru pro funkci redirect
     */
    public function renderLogin($backlink = null) {

        $httpRequest = $this->context->httpRequest;

        if ($httpRequest->getPost() == null) {
            $loginUrl = $this->context->parameters['skautis']['url']. '/Login/?appid='.$this->context->parameters['skautis']['app_id'];
            if ($backlink) {
                $loginUrl .= '&ReturnUrl='.$backlink;
            }
            $this->redirectUrl($loginUrl);
        }
        try {
            \Nette\Diagnostics\Debugger::dump($httpRequest->getPost());
            $this->context->user->login($httpRequest->getPost('skautIS_Token'),$httpRequest->getPost('skautIS_IDUnit'), $httpRequest->getPost('skautIS_IDRole'));
            $this->context->user->setExpiration('+30 minutes', TRUE);
            $this->flashMessage('Přihlášení proběhlo úspěšně');
        }
        catch (\Nette\Security\AuthenticationException $e) {
            $this->flashMessage('Přihlášení prostřednictvím skautIS se nezdařilo', 'error');
        }
        $this->makeRedirectByReturnUrl();
    }

    public function renderLogout($backlink = null) {
        if ($this->context->user->isLoggedIn()) {
            $token = $this->context->user->identity->token;
            $this->context->user->logout(true);
            $logoutUrl = $this->context->parameters['skautis']['url']. '/Login/LogOut.aspx?appid='.$this->context->parameters['skautis']['app_id'].'&Token='.$token;
            if ($backlink) {
                $logoutUrl .= '&ReturnUrl='.$backlink;
            }
            $this->redirectUrl($logoutUrl);

        }
        $this->flashMessage('Odhlášení proběhlo úspěšně');
        $this->makeRedirectByReturnUrl();
    }


    protected function makeRedirectByReturnUrl() {
        if ($returnUrl = $this->getParameter('ReturnUrl')) {
            if (strpos($returnUrl, ':') !== false) {
                $this->redirect($this->getParameter('ReturnUrl'));
            }
            else {
                $this->redirectUrl($returnUrl);
            }
        }
        $this->redirect(':Front:Page:default');
    }




}
