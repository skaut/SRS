<?php

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateRepository;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Mail\SendException;


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

        $form->addCheckbox('active', 'admin.mailing.templates_active');

        $form->addText('subject', 'admin.mailing.templates_subject')
            ->addRule(Form::FILLED, 'admin.mailing.templates_subject_empty');

        $form->addTextArea('text', 'admin.mailing.templates_text')
            ->addRule(Form::FILLED, 'admin.mailing.templates_text_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');


        $form->setDefaults([
            'id' => $id,
            'active' => $this->template->isActive(),
            'subject' => $this->template->getSubject(),
            'text' => $this->template->getText()
        ]);


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
            $this->template->setSubject($values['subject']);
            $this->template->setText($values['text']);

            $this->templateRepository->save($this->template);
        }
    }
}
