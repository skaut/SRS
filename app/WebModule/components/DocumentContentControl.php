<?php

namespace App\WebModule\Components;

use App\Model\CMS\Document\DocumentRepository;
use Nette\Application\UI\Control;

class DocumentContentControl extends Control
{
    /** @var DocumentRepository */
    private $documentRepository;

    public function __construct(DocumentRepository $documentRepository)
    {
        parent::__construct();

        $this->documentRepository = $documentRepository;
    }

    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/document_content.latte');

        $template->heading = $content->getHeading();
        $template->documents = $this->documentRepository->findAllByTagsOrderedByName($content->getTags());

        $template->render();
    }
}