<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use App\Model\Mailing\Repositories\TemplateRepository;

class TemplateFactory
{
    public static function createTemplate(TemplateRepository $templateRepository, string $type): Template
    {
        $template = new Template();
        $template->setType($type);
        $template->setSubject('subject');
        $template->setText('text');
        $template->setActive(true);
        $template->setSystemTemplate(true);
        $templateRepository->save($template);

        return $template;
    }
}
