<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Cms\News;
use App\Model\Cms\Repositories\NewsRepository;
use DateTimeImmutable;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;

/**
 * Formulář pro úpravu aktuality
 */
class NewsFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaná aktualita
     */
    private ?News $news = null;

    public function __construct(private BaseFormFactory $baseFormFactory, private NewsRepository $newsRepository)
    {
    }

    /**
     * Vytvoří formulář
     */
    public function create(?int $id): Form
    {
        $this->news = $this->newsRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $publishedDateTime = new DateTimeControl('admin.cms.news.common.published');
        $publishedDateTime->addRule(Form::FILLED, 'admin.cms.news.form.published_empty');
        $form->addComponent($publishedDateTime, 'published');

        $form->addCheckbox('pinned', 'admin.cms.news.form.pinned');

        $form->addTextArea('text', 'admin.cms.news.common.text')
            ->addRule(Form::FILLED, 'admin.cms.news.form.text_empty')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        if ($this->news) {
            $form->setDefaults([
                'id' => $id,
                'published' => $this->news->getPublished(),
                'pinned' => $this->news->isPinned(),
                'text' => $this->news->getText(),
            ]);
        } else {
            $form->setDefaults([
                'published' => new DateTimeImmutable(),
            ]);
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        if (! $this->news) {
            $this->news = new News();
        }

        $this->news->setText($values->text);
        $this->news->setPublished($values->published);
        $this->news->setPinned($values->pinned);

        $this->newsRepository->save($this->news);
    }
}
