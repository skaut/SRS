<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateRepository;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use function in_array;

/**
 * Formulář pro nastavení šablony automatického e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
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
    public function create(int $id) : Form
    {
        $this->template = $this->templateRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addCheckbox('active', 'admin.mailing.templates.active_form');

        $form->addMultiSelect('recipients', 'admin.mailing.templates.recipients', [
            'user' => 'admin.mailing.templates.send_to_user_form',
            'organizer' => 'admin.mailing.templates.send_to_organizer_form',
        ])->addRule(Form::FILLED, 'admin.mailing.templates.recipients_empty');

        $form->addText('subject', 'admin.mailing.templates.subject')
            ->addRule(Form::FILLED, 'admin.mailing.templates.subject_empty');

        $form->addTextArea('text', 'admin.mailing.templates.text')
            ->addRule(Form::FILLED, 'admin.mailing.templates.text_empty')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $selectedRecipients = [];
        if ($this->template->isSendToOrganizer()) {
            $selectedRecipients[] = 'organizer';
        }

        if ($this->template->isSendToUser()) {
            $selectedRecipients[] = 'user';
        }

        $form->setDefaults([
            'id' => $id,
            'active' => $this->template->isActive(),
            'recipients' => $selectedRecipients,
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
     * @throws ORMException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        $this->template->setActive($values->active);
        $this->template->setSendToUser(in_array('user', $values->recipients));
        $this->template->setSendToOrganizer(in_array('organizer', $values->recipients));
        $this->template->setSubject($values->subject);
        $this->template->setText($values->text);

        $this->templateRepository->save($this->template);
    }
}
