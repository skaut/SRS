<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Content\DocumentContentDto;
use App\Model\Cms\Document\DocumentRepository;
use Nette\Application\UI\Control;
use function array_keys;

/**
 * Komponenta s dokumenty.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DocumentContentControl extends Control
{
    /** @var DocumentRepository */
    private $documentRepository;

    public function __construct(DocumentRepository $documentRepository)
    {
        parent::__construct();

        $this->documentRepository = $documentRepository;
    }

    public function render(DocumentContentDto $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/document_content.latte');

        $roles = $this->presenter->user->roles;

        $template->heading   = $content->getHeading();
        $template->documents = $this->documentRepository->findRolesAllowedByTagsOrderedByName(array_keys($roles), $content->getTags());

        $template->render();
    }
}
