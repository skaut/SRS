<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Mailing\Mail;
use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Mailing\Repositories\TemplateRepository;
use App\Model\Program\Commands\EnqueMail;
use Contributte\Translation\Translator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class EnqueMailHandler implements MessageHandlerInterface
{
    private MailRepository $mailRepository;

    private TemplateRepository $templateRepository;

    private Translator $translator;

    public function __construct(MailRepository $mailRepository, TemplateRepository $templateRepository, Translator $translator)
    {
        $this->mailRepository     = $mailRepository;
        $this->templateRepository = $templateRepository;
        $this->translator         = $translator;
    }

    public function __invoke(EnqueMail $command): void
    {
        $template = $this->templateRepository->findByType($command->getTemplate());

        if (! $template->isActive()) {
            return;
        }

        $subject = $template->getSubject();
        $text    = $template->getText();

        foreach ($template->getVariables() as $variable) {
            $variableName = '%' . $this->translator->translate('common.mailing.variable_name.' . $variable->getName()) . '%';
            $value        = $command->getParameters()[$variable->getName()];

            $subject = str_replace($variableName, strval($value), $subject);
            $text    = str_replace($variableName, strval($value), $text);
        }

        $mail = new Mail();

        if ($command->getRecipientUsers() !== null) {
            $mail->setRecipientUsers($command->getRecipientUsers());
        }

        if ($command->getRecipientEmails() !== null) {
            $mail->setRecipientEmails($command->getRecipientEmails());
        }

        $mail->setSubject($subject);
        $mail->setText($text);
        $mail->setDatetime(new DateTimeImmutable());
        $mail->setAutomatic(true);
        $this->mailRepository->save($mail);
    }
}
