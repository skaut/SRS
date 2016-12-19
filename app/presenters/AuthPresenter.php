<?php

namespace App\Presenters;

class AuthPresenter extends BasePresenter
{
    /**
     * @var \Skautis\Skautis
     * @inject
     */
    public $skautis;

    public function renderLogin($backlink = null) {
        if ($this->getHttpRequest()->getPost() == null) {
            $loginUrl = $this->skautis->getLoginUrl($backlink);
            $this->redirectUrl($loginUrl);
        }

        $this->skautis->setLoginData($_POST);
        $this->user->login();
        $this->user->setExpiration('+30 minutes');
        $this->redirectReturnUrl($this->getParameter('ReturnUrl'));
    }

    public function renderLogout($backlink = null) {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
            $logoutUrl = $this->skautis->getLogoutUrl();
            $this->redirectUrl($logoutUrl);
        }
        $this->redirect(':Web:Page:default');
    }

    private function redirectReturnUrl($returnUrl) {
        if ($returnUrl) {
            if (strpos($returnUrl, ':') !== false)
                $this->redirect($returnUrl);
            else
                $this->redirectUrl($returnUrl);
        }
        $this->redirect(':Web:Page:default'); //TODO redirect podle nastaveni
    }
}