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
            $loginUrl = $this->context->parameters['skautis']['url']. '/Login/?appid='.$this->context->parameters['skautis']['appID'];
            if ($backlink) {
                $loginUrl .= '&ReturnUrl='.$backlink;
            }
            $this->redirectUrl($loginUrl);
        }
        try {
            $this->context->user->login(NULL, $httpRequest->getPost('skautIS_Token'));
            $this->context->user->setExpiration('+30 minutes', TRUE);
            $this->flashMessage('Přihlášení proběhlo úspěšně');
        }
        catch (\Nette\Security\AuthenticationException $e) {
            $this->flashMessage('Přihlášení prostřednictvím skautIS se nezdařilo', 'error');
            //@TODO nemeli bychom presmerovat na nejakou stranku, ktera nevyzaduje prihlaseni?
        }

        if ($this->getParameter('ReturnUrl')) {
            $this->redirect($this->getParameter('ReturnUrl'));
        }
        $this->redirect('Homepage:default');
    }

    public function renderLogout() {
        if ($this->context->user->isLoggedIn()) {
            $token = $this->context->user->identity->token;
            $this->context->user->logout(true);
            $this->redirectUrl($this->context->parameters['skautis']['url']. '/Login/LogOut.aspx?appid='.$this->context->parameters['skautis']['appID'].'&Token='.$token);
        }
        $this->flashMessage('Odhlášení proběhlo úspěšně');
        $this->redirect('Homepage:default');
    }

}
