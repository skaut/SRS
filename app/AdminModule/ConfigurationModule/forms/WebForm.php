<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\PageRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Services\FilesService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;


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

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var FilesService */
    private $filesService;


    /**
     * WebForm constructor.
     * @param BaseForm $baseFormFactory
     * @param PageRepository $pageRepository
     * @param SettingsRepository $settingsRepository
     * @param FilesService $filesService
     */
    public function __construct(BaseForm $baseFormFactory, PageRepository $pageRepository,
                                SettingsRepository $settingsRepository, FilesService $filesService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository = $pageRepository;
        $this->settingsRepository = $settingsRepository;
        $this->filesService = $filesService;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
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

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'footer' => $this->settingsRepository->getValue(Settings::FOOTER),
            'redirectAfterLogin' => $this->settingsRepository->getValue(Settings::REDIRECT_AFTER_LOGIN)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param array $values
     * @throws Nette\Utils\UnknownImageFileException
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function processForm(Form $form, array $values)
    {
        $logo = $values['logo'];
        if ($logo->size > 0) {
            $this->filesService->delete('/logo/' . $this->settingsRepository->getValue(Settings::LOGO));

            $logoName = Strings::webalize($logo->name, '.');
            $this->filesService->save($logo, '/logo/' . $logoName);
            $this->filesService->resizeImage('/logo/' . $logoName, NULL, 100);

            $this->settingsRepository->setValue(Settings::LOGO, $logoName);
        }

        $this->settingsRepository->setValue(Settings::FOOTER, $values['footer']);
        $this->settingsRepository->setValue(Settings::REDIRECT_AFTER_LOGIN, $values['redirectAfterLogin']);
    }
}
