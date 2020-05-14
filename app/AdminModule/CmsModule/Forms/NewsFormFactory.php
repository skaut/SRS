<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Cms\News;
use App\Model\Cms\NewsRepository;
use DateTimeImmutable;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;

/**
 * Formulář pro úpravu aktuality.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaná aktualita.
     */
    private ?News $news = null;

    private BaseFormFactory $baseFormFactory;

    private NewsRepository $newsRepository;

    public function __construct(BaseFormFactory $baseFormFactory, NewsRepository $newsRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->newsRepository  = $newsRepository;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(?int $id) : Form
    {
        $this->news = $this->newsRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $publishedDateTime = new DateTimeControl('admin.cms.news_published');
        $publishedDateTime->addRule(Form::FILLED, 'admin.cms.news_published_empty');
        $form->addComponent($publishedDateTime, 'published');

        $form->addCheckbox('pinned', 'admin.cms.news_edit_pinned');

        $form->addTextArea('text', 'admin.cms.news_text')
            ->addRule(Form::FILLED, 'admin.cms.news_text_empty')
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
     * Zpracuje formulář.
     *
     * @throws ORMException
     */
    public function processForm(Form $form, stdClass $values) : void
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
