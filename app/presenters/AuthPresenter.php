<?php

namespace App\Presenters;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use App\Services\SkautIsService;

class AuthPresenter extends BasePresenter
{
    /**
     * @var SkautIsService
     * @inject
     */
    public $skautIsService;

    /**
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;


    public function actionLogin($backlink = null) {
        if ($this->getHttpRequest()->getPost() == null) {
            $loginUrl = $this->skautIsService->getLoginUrl($backlink);
            $this->redirectUrl($loginUrl);
        }

        $this->skautIsService->setLoginData($_POST);
        $this->user->login();
        $this->user->setExpiration('+30 minutes');
        $this->redirectAfterLogin($this->getParameter('ReturnUrl'));
    }

    public function actionLogout() {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
            $logoutUrl = $this->skautIsService->getLogoutUrl();
            $this->redirectUrl($logoutUrl);
        }
        $this->redirect(':Web:Page:default');
    }

    private function redirectAfterLogin($returnUrl) {
        if ($returnUrl) {
            if (strpos($returnUrl, ':') !== false)
                $this->redirect($returnUrl);
            else
                $this->redirectUrl($returnUrl);
        }

        //pokud neni navratova adresa, presmerovani podle role
        $user = $this->userRepository->findById($this->user->id);

        $redirectByRole = null;
        $multipleRedirects = false;

        foreach ($user->getRoles() as $role) {
            if ($role->getRedirectAfterLogin()) {
                $roleRedirect = $role->getRedirectAfterLogin();

                if ($redirectByRole && $redirectByRole == $roleRedirect) {
                    $multipleRedirects = true;
                    break;
                }
                else {
                    $redirectByRole = $roleRedirect;
                }
            }
        }

        //pokud nema role nastaveno presmerovani, nebo je uzivatel v rolich s ruznymi presmerovani, je presmerovan na vychozi stranku
        if ($redirectByRole && !$multipleRedirects)
            $slug = $redirectByRole;
        else
            $slug = $this->settingsRepository->getValue(Settings::REDIRECT_AFTER_LOGIN);

        $this->redirect(':Web:Page:default', ['slug' => $slug]);
    }
}