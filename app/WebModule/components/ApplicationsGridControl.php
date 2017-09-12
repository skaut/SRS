<?php

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\CMS\Document\Document;
use App\Model\CMS\Document\DocumentRepository;
use App\Model\CMS\Document\TagRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Structure\SubeventRepository;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\FilesService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\DataGrid;
use function ZendTest\Code\Reflection\TestAsset\function1;


/**
 * Komponenta pro správu vlastních přihlášek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $rolesRepostiory;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * ApplicationsGridControl constructor.
     * @param Translator $translator
     * @param ApplicationRepository $applicationRepository
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(Translator $translator, ApplicationRepository $applicationRepository,
                                UserRepository $userRepository, RoleRepository $roleRepository,
                                SubeventRepository $subeventRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository = $userRepository;
        $this->rolesRepostiory = $roleRepository;
        $this->subeventRepository = $subeventRepository;
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
        $user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->applicationRepository->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.id = ' . $user->getId())
        );
        $grid->setPagination(FALSE);

        $grid->addColumnDateTime('applicationDate', 'web.profile.applications_application_date')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnText('roles', 'web.profile.applications_roles')
            ->setRenderer(function ($row) {
                if (!$row->isFirst())
                    return "";

                $roles = [];
                foreach ($row->getUser()->getRoles() as $role) {
                    $roles[] = $role->getName();
                }
                return implode(", ", $roles);
            });

        if ($this->subeventRepository->countExplicitSubevents() > 0) {
            $grid->addColumnText('subevents', 'web.profile.applicationa_subevents')
                ->setRenderer(function ($row) {
                    if (!$row->isFirst())
                        return "";

                    $subevents = [];
                    foreach ($row->getSubevents() as $subevent) {
                        $subevents[] = $subevent->getName();
                    }
                    return implode(", ", $subevents);
                });
        }

        $grid->addColumnNumber('fee', 'web.profile.applications_fee');

        $grid->addColumnText('variable_symbol', 'web.profile.applications_variable_symbol');

        $grid->addColumnDateTime('maturityDate', 'web.profile.applications_maturity_date')
            ->setFormat('j. n. Y');

        $grid->addColumnText('state', 'web.profile.applications_state')
            ->setRenderer(function ($row) {
                $state = $this->translator->translate('common.application_state.' . $row->getState());

                if ($row->getState() == ApplicationState::PAID && $row->getPaymentDate() !== NULL)
                    $state .= ' (' . $row->getPaymentDate()->format('j. n. Y') . ')';

                return $state;
            });

        $rolesOptions = NULL; //$this->rolesRepository->get...();
        $subeventsOptions = NULL;

        if (TRUE) {
            $grid->addInlineAdd()->onControlAdd[] = function ($container) use ($rolesOptions) {
                $container->addText('name', '')
                    ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

                $container->addMultiSelect('tags', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

                $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload')
                    ->addRule(Form::FILLED, 'admin.cms.documents_file_empty');

                $container->addText('description', '');
            };
            $grid->getInlineAdd()->setIcon(NULL);
            $grid->getInlineAdd()->setText($this->translator->translate('web.profile.applications_add_subevents'));
            $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
        }

        if (TRUE) {
            $grid->addInlineEdit()->onControlAdd[] = function ($container) use ($rolesOptions) {
                $container->addText('name', '')
                    ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

                $container->addMultiSelect('tags', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

                $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');

                $container->addText('description', '');
            };
            $grid->getInlineEdit()->setIcon(NULL);
            $grid->getInlineEdit()->setText($this->translator->translate('web.profile.applications_edit'));
            $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
                $container->setDefaults([
                    'name' => $item->getName(),
                    'tags' => $this->tagRepository->findTagsIds($item->getTags()),
                    'description' => $item->getDescription()
                ]);
            };
            $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];
        }

        $grid->addAction('generatePaymentProofBank', 'web.profile.applications_download_payment_proof');
        $grid->allowRowsAction('generatePaymentProofBank', function($item) {
            return $item->getPaymentMethod() == PaymentType::BANK;
        });

        $grid->setColumnsSummary(['fee']);
    }

    /**
     * Zpracuje přidání dokumentu.
     * @param $values
     */
    public function add($values)
    {
//        $file = $values['file'];
//        $path = $this->generatePath($file);
//        $this->filesService->save($file, $path);
//
//        $document = new Document();
//
//        $document->setName($values['name']);
//        $document->setTags($this->tagRepository->findTagsByIds($values['tags']));
//        $document->setFile($path);
//        $document->setDescription($values['description']);
//        $document->setTimestamp(new \DateTime());
//
//        $this->documentRepository->save($document);
//
//        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');
//
//        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu dokumentu.
     * @param $id
     * @param $values
     */
    public function edit($id, $values)
    {
//        $document = $this->documentRepository->findById($id);
//
//        $file = $values['file'];
//        if ($file->size > 0) {
//            $this->filesService->delete($this->documentRepository->find($id)->getFile());
//            $path = $this->generatePath($file);
//            $this->filesService->save($file, $path);
//
//            $document->setFile($path);
//            $document->setTimestamp(new \DateTime());
//        }
//
//        $document->setName($values['name']);
//        $document->setTags($this->tagRepository->findTagsByIds($values['tags']));
//        $document->setDescription($values['description']);
//
//        $this->documentRepository->save($document);
//
//        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');
//
//        $this->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     */
    public function handleGeneratePaymentProofBank($id)
    {
        //TODO
        $user = $this->userRepository->findById($this->user->id);
        if (!$user->getIncomeProofPrintedDate()) {
            $user->setIncomeProofPrintedDate(new \DateTime());
            $this->userRepository->save($user);
        }
        $this->pdfExportService->generatePaymentProof($user, "potvrzeni-o-prijeti-platby.pdf");
    }
}
