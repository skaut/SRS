<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Cms\Repositories\PageRepository;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Services\FilesService;
use App\Services\ISettingsService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function array_key_exists;
use function assert;
use function basename;
use function json_encode;

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

    private ISettingsService $settingsService;

    private FilesService $filesService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        PageRepository $pageRepository,
        ISettingsService $settingsService,
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
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $logo = $this->settingsService->getValue(Settings::LOGO);
        $form->addUpload('logo', 'admin.configuration.web_logo')
            ->setHtmlAttribute('accept', 'image/*')
            ->setHtmlAttribute('data-show-preview', 'true')
            ->setHtmlAttribute('data-initial-preview', json_encode([$logo]))
            ->setHtmlAttribute('data-initial-preview-config', json_encode([['caption' => basename($logo)]]))
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.configuration.web_logo_format');

        $form->addText('footer', 'admin.configuration.web_footer');

        $redirectAfterLoginOptions = $this->pageRepository->getPagesOptions();
        $redirectAfterLoginValue   = $this->settingsService->getValue(Settings::REDIRECT_AFTER_LOGIN);

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
    public function processForm(Form $form, stdClass $values): void
    {
        $logo = $values->logo;
        assert($logo instanceof FileUpload);
        if ($logo->getError() == UPLOAD_ERR_OK) {
            $this->filesService->delete($this->settingsService->getValue(Settings::LOGO));
            $path = $this->filesService->save($logo, 'logo', false, $logo->name);
            $this->filesService->resizeImage($path, null, 100);
            $this->settingsService->setValue(Settings::LOGO, $path);
        }

        $this->settingsService->setValue(Settings::FOOTER, $values->footer);
        $this->settingsService->setValue(Settings::REDIRECT_AFTER_LOGIN, $values->redirectAfterLogin);
        $this->settingsService->setValue(Settings::GA_ID, $values->ga_id);
    }
}
