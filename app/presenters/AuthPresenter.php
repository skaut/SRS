<?php

namespace App\Presenters;

use App\Services\SkautIsService;

class AuthPresenter extends BasePresenter
{
    /**
     * @var SkautIsService
     * @inject
     */
    public $skautIsService;

    public function actionLogin($backlink = null) {
        if ($this->getHttpRequest()->getPost() == null) {
            $loginUrl = $this->skautIsService->getLoginUrl($backlink);
            $this->redirectUrl($loginUrl);
        }

        $this->skautIsService->setLoginData($_POST);
        $this->user->login();
        $this->user->setExpiration('+30 minutes');
        $this->redirectReturnUrl($this->getParameter('ReturnUrl'));
    }

    public function actionLogout() {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
            $logoutUrl = $this->skautIsService->getLogoutUrl();
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