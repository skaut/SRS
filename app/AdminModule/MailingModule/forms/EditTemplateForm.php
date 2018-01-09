<?php

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro nastavení šablony automatického e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class EditTemplateForm extends Nette\Object
{
    /**
     * Upravovaná šablona.
     * @var Template
     */
    private $template;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var TemplateRepository */
    private $templateRepository;


    /**
     * SendForm constructor.
     * @param BaseForm $baseFormFactory
     * @param TemplateRepository $templateRepository
     */
    public function __construct(BaseForm $baseFormFactory, TemplateRepository $templateRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->template = $this->templateRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addCheckbox('active', 'admin.mailing.templates_active_form');

        $form->addMultiSelect('recipients', 'admin.mailing.templates_recipients', [
            'user' => 'admin.mailing.templates_send_to_user_form',
            'organizer' => 'admin.mailing.templates_send_to_organizer_form'
        ])->addRule(Form::FILLED, 'admin.mailing.templates_recipients_empty');

        $form->addText('subject', 'admin.mailing.templates_subject')
            ->addRule(Form::FILLED, 'admin.mailing.templates_subject_empty');

        $form->addTextArea('text', 'admin.mailing.templates_text')
            ->addRule(Form::FILLED, 'admin.mailing.templates_text_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');


        $selectedRecipients = [];
        if ($this->template->isSendToOrganizer())
            $selectedRecipients[] = 'organizer';
        if ($this->template->isSendToUser())
            $selectedRecipients[] = 'user';

        $form->setDefaults([
            'id' => $id,
            'active' => $this->template->isActive(),
            'recipients' => $selectedRecipients,
            'subject' => $this->template->getSubject(),
            'text' => $this->template->getText()
        ]);

        if ($this->template->isSendToUser())


        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            $this->template->setActive($values['active']);
            $this->template->setSendToUser(in_array('user', $values['recipients']));
            $this->template->setSendToOrganizer(in_array('organizer', $values['recipients']));
            $this->template->setSubject($values['subject']);
            $this->template->setText($values['text']);

            $this->templateRepository->save($this->template);
        }
    }
}
