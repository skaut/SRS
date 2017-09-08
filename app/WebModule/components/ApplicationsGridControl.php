<?php

namespace App\WebModule\Components;

use App\Model\CMS\Document\Document;
use App\Model\CMS\Document\DocumentRepository;
use App\Model\CMS\Document\TagRepository;
use App\Services\FilesService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu vlastních přihlášek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var DocumentRepository */
    private $documentRepository;

    /** @var FilesService */
    private $filesService;

    /** @var TagRepository */
    private $tagRepository;


    /**
     * ApplicationsGridControl constructor.
     * @param Translator $translator
     * @param DocumentRepository $documentRepository
     * @param TagRepository $tagRepository
     * @param FilesService $filesService
     */
    public function __construct(Translator $translator, DocumentRepository $documentRepository,
                                TagRepository $tagRepository, FilesService $filesService)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->documentRepository = $documentRepository;
        $this->tagRepository = $tagRepository;
        $this->filesService = $filesService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/applications_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentApplicationsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->applicationRepository->createQueryBuilder('a'));
        $grid->setPagination(FALSE);

        $grid->addColumnDateTime('timestamp', 'admin.cms.documents_timestamp')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnText('roles', 'admin.cms.documents_name');

        $grid->addColumnText('subevents', 'admin.cms.documents_description');

        $grid->addColumnDateTime('maturity', 'admin.cms.documents_timestamp')
            ->setFormat('j. n. Y');


        $rolesOptions = $this->rolesRepository->get...();
        $subeventsOptions = $this->tagRepository->getTagsOptions();

        $grid->addInlineAdd()->onControlAdd[] = function ($container) use ($tagsOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload')
                ->addRule(Form::FILLED, 'admin.cms.documents_file_empty');

            $container->addText('description', '');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) use ($tagsOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');

            $container->addText('description', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container->setDefaults([
                'name' => $item->getName(),
                'tags' => $this->tagRepository->findTagsIds($item->getTags()),
                'description' => $item->getDescription()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];
    }

    /**
     * Zpracuje přidání dokumentu.
     * @param $values
     */
    public function add($values)
    {
        $file = $values['file'];
        $path = $this->generatePath($file);
        $this->filesService->save($file, $path);

        $document = new Document();

        $document->setName($values['name']);
        $document->setTags($this->tagRepository->findTagsByIds($values['tags']));
        $document->setFile($path);
        $document->setDescription($values['description']);
        $document->setTimestamp(new \DateTime());

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu dokumentu.
     * @param $id
     * @param $values
     */
    public function edit($id, $values)
    {
        $document = $this->documentRepository->findById($id);

        $file = $values['file'];
        if ($file->size > 0) {
            $this->filesService->delete($this->documentRepository->find($id)->getFile());
            $path = $this->generatePath($file);
            $this->filesService->save($file, $path);

            $document->setFile($path);
            $document->setTimestamp(new \DateTime());
        }

        $document->setName($values['name']);
        $document->setTags($this->tagRepository->findTagsByIds($values['tags']));
        $document->setDescription($values['description']);

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }
}
