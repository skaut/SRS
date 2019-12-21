<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\MailService;
use App\Services\SettingsService;
use Nette;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;
use function md5;
use function mt_rand;
use function substr;
use function uniqid;

/**
 * Formulář pro nastavení mailingu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingFormFactory
{
    use Nette\SmartObject;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var SettingsService */
    private $settingsService;

    /** @var MailService */
    private $mailService;

    /** @var LinkGenerator */
    private $linkGenerator;

    public function __construct(
        BaseFormFactory $baseForm,
        SettingsService $settingsService,
        MailService $mailService,
        LinkGenerator $linkGenerator
    ) {
        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
        $this->mailService     = $mailService;
        $this->linkGenerator   = $linkGenerator;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create(int $id) : BaseForm
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addText('seminarEmail', 'admin.configuration.mailing_email')
            ->addRule(Form::FILLED, 'admin.configuration.mailing_email_empty')
            ->addRule(Form::EMAIL, 'admin.configuration.mailing_email_format');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'seminarEmail' => $this->settingsService->getValue(Settings::SEMINAR_EMAIL),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Nette\Application\UI\InvalidLinkException
     * @throws SettingsException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function processForm(BaseForm $form, stdClass $values) : void
    {
        if ($this->settingsService->getValue(Settings::SEMINAR_EMAIL) === $values->seminarEmail) {
            return;
        }

        $this->settingsService->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, $values->seminarEmail);

        $verificationCode = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        $this->settingsService->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, $verificationCode);

        $link = $this->linkGenerator->link('Action:Mailing:verify', ['code' => $verificationCode]);

        $this->mailService->sendMailFromTemplate(
            null,
            $values->seminarEmail,
            Template::EMAIL_VERIFICATION,
            [
                TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::EMAIL_VERIFICATION_LINK => $link,
            ],
            true
        );
    }
}
