<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Content\ContentDto;
use App\WebModule\Forms\ContactForm;
use App\WebModule\Forms\IContactFormFactory;
use Nette\Application\UI\Control;

/**
 * Komponenta s kontaktním formulářem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContactFormContentControl extends Control
{
    private IContactFormFactory $contactFormFactory;

    public function __construct(IContactFormFactory $contactFormFactory)
    {
        $this->contactFormFactory = $contactFormFactory;
    }

    public function render(ContentDto $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/contact_form_content.latte');

        $template->heading = $content->getHeading();

        $template->render();
    }

    public function createComponentContactForm() : ContactForm
    {
        $form = $this->contactFormFactory->create();

        $form->onSave[] = function () : void {
            $this->getPresenter()->flashMessage('web.contact_form_content.send_message_successful', 'success');

            $this->getPresenter()->redirect('this');
        };

        return $form;
    }
}
