<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Cms\Repositories\PageRepository;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\FilesService;
use App\Services\QueryBus;
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

use const JSON_THROW_ON_ERROR;
use const UPLOAD_ERR_OK;

/**
 * Formulář pro nastavení webové prezentace
 */
class WebFormFactory
{
    use Nette\SmartObject;

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private PageRepository $pageRepository,
        private FilesService $filesService
    ) {
    }

    /**
     * Vytvoří formulář
     *
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $logo = $this->queryBus->handle(new SettingStringValueQuery(Settings::LOGO));
        $form->addUpload('logo', 'admin.configuration.web_logo')
            ->setHtmlAttribute('accept', 'image/*')
            ->setHtmlAttribute('data-show-preview', 'true')
            ->setHtmlAttribute('data-initial-preview', json_encode([$logo], JSON_THROW_ON_ERROR))
            ->setHtmlAttribute('data-initial-preview-config', json_encode([['caption' => basename($logo)]]))
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.configuration.web_logo_format');

        $form->addText('footer', 'admin.configuration.web_footer');

        $redirectAfterLoginOptions = $this->pageRepository->getPagesOptions();
        $redirectAfterLoginValue   = $this->queryBus->handle(new SettingStringValueQuery(Settings::REDIRECT_AFTER_LOGIN));

        $form->addSelect('redirectAfterLogin', 'admin.configuration.web_redirect_after_login', $redirectAfterLoginOptions)
            ->addRule(Form::FILLED, 'admin.configuration.web_redirect_after_login_empty');

//        $form->addText('ga_id', 'admin.configuration.web_ga_id');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'footer' => $this->queryBus->handle(new SettingStringValueQuery(Settings::FOOTER)),
            'redirectAfterLogin' => array_key_exists($redirectAfterLoginValue, $redirectAfterLoginOptions) ? $redirectAfterLoginValue : null,
//            'ga_id' => $this->queryBus->handle(new SettingStringValueQuery(Settings::GA_ID)),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář
     *
     * @throws Nette\Utils\UnknownImageFileException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $logo = $values->logo;
        assert($logo instanceof FileUpload);
        if ($logo->getError() === UPLOAD_ERR_OK) {
            $this->filesService->delete($this->queryBus->handle(new SettingStringValueQuery(Settings::LOGO)));
            $path = $this->filesService->save($logo, 'logo', false, $logo->name);
            $this->filesService->resizeImage($path, null, 100);
            $this->commandBus->handle(new SetSettingStringValue(Settings::LOGO, $path));
        }

        $this->commandBus->handle(new SetSettingStringValue(Settings::FOOTER, $values->footer));
        $this->commandBus->handle(new SetSettingStringValue(Settings::REDIRECT_AFTER_LOGIN, $values->redirectAfterLogin));
//        $this->commandBus->handle(new SetSettingStringValue(Settings::GA_ID, $values->ga_id));
    }
}
