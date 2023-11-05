<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IProgramAttendeesGridControlFactory;
use App\AdminModule\ProgramModule\Components\IProgramBlocksGridControlFactory;
use App\AdminModule\ProgramModule\Components\ProgramAttendeesGridControl;
use App\AdminModule\ProgramModule\Components\ProgramBlocksGridControl;
use App\AdminModule\ProgramModule\Forms\BlockFormFactory;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Commands\RemoveProgram;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Http\Session;
use Throwable;

/**
 * Presenter obsluhující správu programových bloků.
 */
class BlocksPresenter extends ProgramBasePresenter
{
    #[Inject]
    public CommandBus $commandBus;

    #[Inject]
    public BlockRepository $blockRepository;

    #[Inject]
    public ProgramRepository $programRepository;

    #[Inject]
    public IProgramBlocksGridControlFactory $programBlocksGridControlFactory;

    #[Inject]
    public IProgramAttendeesGridControlFactory $programAttendeesGridControlFactory;

    #[Inject]
    public BlockFormFactory $blockFormFactory;

    #[Inject]
    public Session $session;

    public function renderDefault(): void
    {
        $this->template->emptyUserInfo = empty($this->dbUser->getAbout());

        $this->session->getSection('srs')->programId = 0;
    }

    /** @throws Throwable */
    public function renderDetail(int $id): void
    {
        $block = $this->blockRepository->findById($id);

        $this->template->block                              = $block;
        $this->template->programId                          = $this->session->getSection('srs')->programId;
        $this->template->userAllowedModifySchedule          = $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_SCHEDULE)
            && $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_MODIFY_SCHEDULE));
        $this->template->programMandatoryTypeVoluntary      = ProgramMandatoryType::VOLUNTARY;
        $this->template->programMandatoryTypeMandatory      = ProgramMandatoryType::MANDATORY;
        $this->template->programMandatoryTypeAutoRegistered = ProgramMandatoryType::AUTO_REGISTERED;
    }

    /** @throws AbortException */
    public function renderEdit(int $id): void
    {
        $block = $this->blockRepository->findById($id);

        if (! $this->userRepository->findById($this->getUser()->getId())->isAllowedModifyBlock($block)) {
            $this->flashMessage('admin.program.blocks.message.edit_not_allowed', 'danger');
            $this->redirect('Blocks:default');
        }

        $this->template->block = $block;
    }

    /**
     * Zobrazí přehled účastníků u vybraného programu.
     *
     * @throws AbortException
     */
    public function handleShowAttendees(int $programId): void
    {
        $this->session->getSection('srs')->programId = $programId;

        $this->template->programId = $programId;

        if ($this->isAjax()) {
            $this->redrawControl('programs');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Odstraní vybraný program.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDeleteProgram(int $programId): void
    {
        $program = $this->programRepository->findById($programId);

        if (
            ! $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_SCHEDULE) ||
            ! $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_MODIFY_SCHEDULE))
        ) {
            $this->flashMessage('admin.program.blocks.programs.message.modify_schedule_not_allowed', 'danger');
        } else {
            $this->commandBus->handle(new RemoveProgram($program));
            $this->flashMessage('admin.program.blocks.programs.message.delete_success', 'success');
        }

        $this->redirect('this');
    }

    protected function createComponentProgramBlocksGrid(): ProgramBlocksGridControl
    {
        return $this->programBlocksGridControlFactory->create();
    }

    protected function createComponentProgramAttendeesGrid(): ProgramAttendeesGridControl
    {
        return $this->programAttendeesGridControlFactory->create();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function createComponentBlockForm(): Form
    {
        return $this->blockFormFactory->create((int) $this->getParameter('id'), $this->getUser()->getId());
    }
}
