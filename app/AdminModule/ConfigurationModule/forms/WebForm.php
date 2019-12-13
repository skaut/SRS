<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\PageRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\FilesService;
use App\Services\SettingsService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení webové prezentace.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class WebForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var PageRepository */
    private $pageRepository;

    /** @var SettingsService */
    private $settingsService;

    /** @var FilesService */
    private $filesService;


    public function __construct(
        BaseForm $baseFormFactory,
        PageRepository $pageRepository,
        SettingsService $settingsService,
        FilesService $filesService
    ) {
        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository  = $pageRepository;
        $this->settingsService = $settingsService;
        $this->filesService    = $filesService;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addUpload('logo', 'admin.configuration.web_new_logo')
            ->setAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.configuration.web_new_logo_format');

        $form->addText('footer', 'admin.configuration.web_footer');

        $form->addSelect('redirectAfterLogin', 'admin.configuration.web_redirect_after_login', $this->pageRepository->getPagesOptions())
            ->addRule(Form::FILLED, 'admin.configuration.web_redirect_after_login_empty');

        $form->addText('ga_id', 'admin.configuration.web_ga_id');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'footer' => $this->settingsService->getValue(Settings::FOOTER),
            'redirectAfterLogin' => $this->settingsService->getValue(Settings::REDIRECT_AFTER_LOGIN),
            'ga_id' => $this->settingsService->getValue(Settings::GA_ID),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws Nette\Utils\UnknownImageFileException
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $logo = $values['logo'];
        if ($logo->size > 0) {
            $this->filesService->delete('/logo/' . $this->settingsService->getValue(Settings::LOGO));

            $logoName = Strings::webalize($logo->name, '.');
            $this->filesService->save($logo, '/logo/' . $logoName);
            $this->filesService->resizeImage('/logo/' . $logoName, null, 100);

            $this->settingsService->setValue(Settings::LOGO, $logoName);
        }

        $this->settingsService->setValue(Settings::FOOTER, $values['footer']);
        $this->settingsService->setValue(Settings::REDIRECT_AFTER_LOGIN, $values['redirectAfterLogin']);
        $this->settingsService->setValue(Settings::GA_ID, $values['ga_id']);
    }
}
