<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands\Handlers;

use App\Model\Mailing\Commands\CreateTemplateMail;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailQueue;
use App\Model\Mailing\Recipient;
use App\Model\Mailing\Repositories\MailQueueRepository;
use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Mailing\Repositories\TemplateRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Localization\Translator;

use function str_replace;
use function strval;

class CreateTemplateMailHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailRepository $mailRepository,
        private readonly MailQueueRepository $mailQueueRepository,
        private readonly TemplateRepository $templateRepository,
        private readonly Translator $translator,
    ) {
    }

    public function __invoke(CreateTemplateMail $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
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

            $mail       = new Mail();
            $recipients = new ArrayCollection();

            if ($command->getRecipientUsers() !== null) {
                $mail->setRecipientUsers($command->getRecipientUsers());
                foreach ($command->getRecipientUsers() as $user) {
                    $this->addRecipient($recipients, Recipient::createFromUser($user));
                }
            }

            if ($command->getRecipientEmails() !== null) {
                $mail->setRecipientEmails($command->getRecipientEmails()->toArray());
                foreach ($command->getRecipientEmails() as $email) {
                    $this->addRecipient($recipients, new Recipient($email));
                }
            }

            $mail->setSubject($subject);
            $mail->setText($text);
            $mail->setDatetime(new DateTimeImmutable());
            $mail->setAutomatic(true);

            $this->mailRepository->save($mail);

            foreach ($recipients as $recipient) {
                $this->mailQueueRepository->save(new MailQueue($recipient, $mail, new DateTimeImmutable()));
            }
        });
    }

    /** @param Collection<int, Recipient> $recipients */
    private function addRecipient(Collection $recipients, Recipient $recipient): void
    {
        if ($recipient->isValid() && ! $recipients->contains($recipient)) {
            $recipients->add($recipient);
        }
    }
}
