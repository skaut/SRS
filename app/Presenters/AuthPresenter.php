<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\MailService;
use App\Services\SettingsService;
use App\Services\SkautIsService;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Application\AbortException;
use Nette\Security\AuthenticationException;
use Nette\Security\Identity;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

use function assert;
use function strpos;

/**
 * Presenter obsluhující přihlašování a odhlašování pomocí skautIS.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class AuthPresenter extends BasePresenter
{
    /** @inject */
    public SkautIsService $skautIsService;

    /** @inject */
    public SettingsService $settingsService;

    /** @inject */
    public UserRepository $userRepository;

    /** @inject */
    public MailService $mailService;

    /**
     * Přesměruje na přihlašovací stránku skautIS, nastaví přihlášení.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws AuthenticationException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function actionLogin(string $backlink = ''): void
    {
        if (empty($this->getHttpRequest()->getPost())) {
            $loginUrl = $this->skautIsService->getLoginUrl($backlink);
            $this->redirectUrl($loginUrl);
        }

        $this->skautIsService->setLoginData($_POST);
        $this->user->login('');
        $this->user->setExpiration('+30 minutes');

        /** @var Identity $userIdentity */
        $userIdentity = $this->user->identity;
        if ($userIdentity->data['firstLogin']) {
            $user = $this->userRepository->findById($this->user->id);

            assert($user instanceof User);
            $this->mailService->sendMailFromTemplate(new ArrayCollection([$user]), null, Template::SIGN_IN, [
                TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
            ]);
        }

        $this->redirectAfterLogin($this->getParameter('ReturnUrl'));
    }

    /**
     * Přesměruje na odhlašovací stránku skautIS.
     *
     * @throws AbortException
     */
    public function actionLogout(): void
    {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
            $logoutUrl = $this->skautIsService->getLogoutUrl();
            $this->redirectUrl($logoutUrl);
        }

        $this->redirect(':Web:Page:default');
    }

    /**
     * Provede přesměrování po úspěšném přihlášení, v závislosti na nastavení, nastavení role nebo returnUrl.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    private function redirectAfterLogin(?string $returnUrl): void
    {
        if ($returnUrl) {
            if (strpos($returnUrl, ':') !== false) {
                $this->redirect($returnUrl);
            } else {
                $this->redirectUrl($returnUrl);
            }
        }

        //pokud neni navratova adresa, presmerovani podle role
        $user = $this->userRepository->findById($this->user->id);

        $redirectByRole    = null;
        $multipleRedirects = false;

        foreach ($user->getRoles() as $role) {
            if ($role->getRedirectAfterLogin()) {
                $roleRedirect = $role->getRedirectAfterLogin();

                if ($redirectByRole && $redirectByRole === $roleRedirect) {
                    $multipleRedirects = true;
                    break;
                } else {
                    $redirectByRole = $roleRedirect;
                }
            }
        }

        //pokud nema role nastaveno presmerovani, nebo je uzivatel v rolich s ruznymi presmerovani, je presmerovan na vychozi stranku
        if ($redirectByRole && ! $multipleRedirects) {
            $slug = $redirectByRole;
        } else {
            $slug = $this->settingsService->getValue(Settings::REDIRECT_AFTER_LOGIN);
        }

        $this->redirect(':Web:Page:default', ['slug' => $slug]);
    }
}
