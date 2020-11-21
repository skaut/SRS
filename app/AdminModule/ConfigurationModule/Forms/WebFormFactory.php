<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Cms\PageRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\FilesService;
use App\Services\SettingsService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;
use stdClass;
use Throwable;
use function array_key_exists;
use const UPLOAD_ERR_OK;

/**
 * Formulář pro nastavení webové prezentace.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class WebFormFactory
{
    use Nette\SmartObject;

    private BaseFormFactory $baseFormFactory;

    private PageRepository $pageRepository;

    private SettingsService $settingsService;

    private FilesService $filesService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
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
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $form->addUpload('logo', 'admin.configuration.web_new_logo')
            ->setHtmlAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.configuration.web_new_logo_format');

        $form->addText('footer', 'admin.configuration.web_footer');

        $redirectAfterLoginOptions = $this->pageRepository->getPagesOptions();
        $redirectAfterLoginValue = $this->settingsService->getValue(Settings::REDIRECT_AFTER_LOGIN);

        $form->addSelect('redirectAfterLogin', 'admin.configuration.web_redirect_after_login', $redirectAfterLoginOptions)
            ->addRule(Form::FILLED, 'admin.configuration.web_redirect_after_login_empty');

        $form->addText('ga_id', 'admin.configuration.web_ga_id');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'footer' => $this->settingsService->getValue(Settings::FOOTER),
            'redirectAfterLogin' => array_key_exists($redirectAfterLoginValue, $redirectAfterLoginOptions) ? $redirectAfterLoginValue : null,
            'ga_id' => $this->settingsService->getValue(Settings::GA_ID),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Nette\Utils\UnknownImageFileException
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        /** @var FileUpload $logo */
        $logo = $values->logo;
        if ($logo->getError() == UPLOAD_ERR_OK) {
//            $this->filesService->delete('/logo/' . $this->settingsService->getValue(Settings::LOGO));
            $logoName = Strings::webalize($logo->name, '.');
            $this->filesService->save($logo, '/logo/' . $logoName);
            $this->filesService->resizeImage('/logo/' . $logoName, null, 100);

            $this->settingsService->setValue(Settings::LOGO, $logoName);
        }

        $this->settingsService->setValue(Settings::FOOTER, $values->footer);
        $this->settingsService->setValue(Settings::REDIRECT_AFTER_LOGIN, $values->redirectAfterLogin);
        $this->settingsService->setValue(Settings::GA_ID, $values->ga_id);
    }
}
