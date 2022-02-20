<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Mailing\Repositories\TemplateRepository;
use App\Model\Mailing\Template;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Formulář pro nastavení šablony automatického e-mailu.
 */
class EditTemplateFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaná šablona.
     */
    private ?Template $template = null;

    private BaseFormFactory $baseFormFactory;

    private TemplateRepository $templateRepository;

    public function __construct(BaseFormFactory $baseFormFactory, TemplateRepository $templateRepository)
    {
        $this->baseFormFactory    = $baseFormFactory;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id): Form
    {
        $this->template = $this->templateRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addCheckbox('active', 'admin.mailing.templates.active_form');

        $form->addText('subject', 'admin.mailing.templates.subject')
            ->addRule(Form::FILLED, 'admin.mailing.templates.subject_empty');

        $form->addTextArea('text', 'admin.mailing.templates.text')
            ->addRule(Form::FILLED, 'admin.mailing.templates.text_empty')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $form->setDefaults([
            'id' => $id,
            'active' => $this->template->isActive(),
            'subject' => $this->template->getSubject(),
            'text' => $this->template->getText(),
        ]);

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        $this->template->setActive($values->active);
        $this->template->setSubject($values->subject);
        $this->template->setText($values->text);

        $this->templateRepository->save($this->template);
    }
}
