<?php

namespace App\Presenters;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use App\Services\MailService;
use App\Services\SkautIsService;


/**
 * Presenter obsluhující přihlašování a odhlašování pomocí skautIS.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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

    /**
     * @var MailService
     * @inject
     */
    public $mailService;


    /**
     * Přesměruje na přihlašovací stránku skautIS, nastaví přihlášení.
     * @param null $backlink
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     * @throws \Nette\Security\AuthenticationException
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function actionLogin($backlink = NULL)
    {
        if ($this->getHttpRequest()->getPost() == NULL) {
            $loginUrl = $this->skautIsService->getLoginUrl($backlink);
            $this->redirectUrl($loginUrl);
        }

        $this->skautIsService->setLoginData($_POST);
        $this->user->login();
        $this->user->setExpiration('+30 minutes');

        if ($this->user->identity->data['firstLogin']) {
            $user = $this->userRepository->findById($this->user->id);

            $this->mailService->sendMailFromTemplate($user, '', Template::SIGN_IN, [
                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
            ]);
        }

        $this->redirectAfterLogin($this->getParameter('ReturnUrl'));
    }

    /**
     * Přesměruje na odhlašovací stránku skautIS.
     */
    public function actionLogout()
    {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(TRUE);
            $logoutUrl = $this->skautIsService->getLogoutUrl();
            $this->redirectUrl($logoutUrl);
        }

        $this->redirect(':Web:Page:default');
    }

    /**
     * Provede přesměrování po úspěšném přihlášení, v závislosti na nastavení, nastavení role nebo returnUrl.
     * @param $returnUrl
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     */
    private function redirectAfterLogin($returnUrl)
    {
        if ($returnUrl) {
            if (strpos($returnUrl, ':') !== FALSE)
                $this->redirect($returnUrl);
            else
                $this->redirectUrl($returnUrl);
        }

        //pokud neni navratova adresa, presmerovani podle role
        $user = $this->userRepository->findById($this->user->id);

        $redirectByRole = NULL;
        $multipleRedirects = FALSE;

        foreach ($user->getRoles() as $role) {
            if ($role->getRedirectAfterLogin()) {
                $roleRedirect = $role->getRedirectAfterLogin();

                if ($redirectByRole && $redirectByRole == $roleRedirect) {
                    $multipleRedirects = TRUE;
                    break;
                } else {
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
