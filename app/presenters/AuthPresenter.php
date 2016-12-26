<?php

namespace App\Presenters;

class AuthPresenter extends BasePresenter
{
    /**
     * @var \Skautis\Skautis
     * @inject
     */
    public $skautIS;

    public function actionLogin($backlink = null) {
        if ($this->getHttpRequest()->getPost() == null) {
            $loginUrl = $this->skautIS->getLoginUrl($backlink);
            $this->redirectUrl($loginUrl);
        }

        $this->skautIS->setLoginData($_POST);
        $this->user->login();
        $this->user->setExpiration('+30 minutes');
        $this->redirectReturnUrl($this->getParameter('ReturnUrl'));
    }

    public function actionLogout() {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
            $logoutUrl = $this->skautIS->getLogoutUrl();
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