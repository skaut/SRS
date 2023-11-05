<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\DocumentContentDto;
use App\Model\Cms\Repositories\DocumentRepository;

use function array_keys;

/**
 * Komponenta obsahu s dokumenty.
 */
class DocumentContentControl extends BaseContentControl
{
    public function __construct(private readonly DocumentRepository $documentRepository)
    {
    }

    public function render(DocumentContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/document_content.latte');

        $roles = $this->presenter->getUser()->getRoles();

        $template->heading   = $content->getHeading();
        $template->documents = $this->documentRepository->findRolesAllowedByTagsOrderedByName(array_keys($roles), $content->getTags());

        $template->render();
    }
}
