<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Nette;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;


/**
 * Formulář pro nastavení mailingu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingForm extends Nette\Object
{
    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var MailService */
    private $mailService;

    /** @var LinkGenerator */
    private $linkGenerator;


    /**
     * MailingForm constructor.
     * @param BaseForm $baseForm
     * @param SettingsRepository $settingsRepository
     * @param UserRepository $userRepository
     * @param MailService $mailService
     * @param LinkGenerator $linkGenerator
     */
    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository,
                                UserRepository $userRepository, MailService $mailService, LinkGenerator $linkGenerator)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->userRepository = $userRepository;
        $this->mailService = $mailService;
        $this->linkGenerator = $linkGenerator;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addText('seminarEmail', 'admin.configuration.mailing_email')
            ->addRule(Form::FILLED, 'admin.configuration.mailing_email_empty')
            ->addRule(Form::EMAIL, 'admin.configuration.mailing_email_format');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'seminarEmail' => $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws Nette\Application\UI\InvalidLinkException
     * @throws \App\Model\Settings\SettingsException
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if ($this->settingsRepository->getValue(Settings::SEMINAR_EMAIL) != $values['seminarEmail']) {
            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, $values['seminarEmail']);

            $verificationCode = substr(md5(uniqid(mt_rand(), TRUE)), 0, 8);
            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, $verificationCode);

            $link = $this->linkGenerator->link('Action:Mailing:verify', ['code' => $verificationCode]);

            $this->mailService->sendMailFromTemplate(NULL, $values['seminarEmail'],
                Template::EMAIL_VERIFICATION, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::EMAIL_VERIFICATION_LINK => $link],
                TRUE);
        }
    }
}
