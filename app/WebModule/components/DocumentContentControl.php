<?php

namespace App\WebModule\Components;

use App\Model\CMS\Content\DocumentContent;
use App\Model\CMS\Document\DocumentRepository;
use Nette\Application\UI\Control;


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


    /**
     * DocumentContentControl constructor.
     * @param DocumentRepository $documentRepository
     */
    public function __construct(DocumentRepository $documentRepository)
    {
        parent::__construct();

        $this->documentRepository = $documentRepository;
    }

    /**
     * @param $content
     */
    public function render(DocumentContent $content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/document_content.latte');

        $roles = $this->presenter->user->roles;
        
        $template->heading = $content->getHeading();
        $template->documents = $this->documentRepository->findRolesAllowedByTagsOrderedByName(array_keys($roles), $content->getTags());

        $template->render();
    }
}
