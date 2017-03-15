<?php

namespace App\AdminModule\ConfigurationModule\Forms;


use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\PageRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Services\FilesService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;

class WebForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseFormFactory;

    /** @var PageRepository */
    private $pageRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var FilesService */
    private $filesService;

    public function __construct(BaseForm $baseFormFactory, PageRepository $pageRepository,
                                SettingsRepository $settingsRepository, FilesService $filesService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository = $pageRepository;
        $this->settingsRepository = $settingsRepository;
        $this->filesService = $filesService;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addUpload('logo', 'admin.configuration.web_new_logo')
            ->setAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.configuration.web_new_logo_format');

        $form->addText('footer', 'admin.configuration.web_footer');

        $form->addSelect('redirectAfterLogin', 'admin.configuration.web_redirect_after_login', $this->pageRepository->getPagesOptions())
            ->addRule(Form::FILLED, 'admin.configuration.web_redirect_after_login_empty');

        $form->addCheckbox('displayUsersRoles', 'admin.configuration.web_display_users_roles');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'footer' => $this->settingsRepository->getValue(Settings::FOOTER),
            'redirectAfterLogin' => $this->settingsRepository->getValue(Settings::REDIRECT_AFTER_LOGIN),
            'displayUsersRoles' => $this->settingsRepository->getValue(Settings::DISPLAY_USERS_ROLES)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values)
    {
        $logo = $values['logo'];
        if ($logo->size > 0) {
            $this->filesService->delete('/logo/' . $this->settingsRepository->getValue(Settings::LOGO));

            $logoName = Strings::webalize($logo->name, '.');
            $this->filesService->save($logo, '/logo/' . $logoName);
            $this->filesService->resizeImage('/logo/' . $logoName, null, 100);

            $this->settingsRepository->setValue(Settings::LOGO, $logoName);
        }

        $this->settingsRepository->setValue(Settings::FOOTER, $values['footer']);
        $this->settingsRepository->setValue(Settings::REDIRECT_AFTER_LOGIN, $values['redirectAfterLogin']);
        $this->settingsRepository->setValue(Settings::DISPLAY_USERS_ROLES, $values['displayUsersRoles']);
    }
}
