<?php

namespace App\AdminModule\Presenters;


use App\AdminModule\Components\IUsersGridControlFactory;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Nette\Http\Session;

class UsersPresenter extends AdminBasePresenter
{
    protected $resource = Resource::USERS;

    /**
     * @var IUsersGridControlFactory
     * @inject
     */
    public $usersGridControlFactory;

    /**
     * @var PdfExportService
     * @inject
     */
    public $pdfExportService;

    /**
     * @var ExcelExportService
     * @inject
     */
    public $excelExportService;

    /**
     * @var Session
     * @inject
     */
    public $session;


    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function actionGeneratePaymentProofs() {
        $ids = $this->session->getSection('datagrid-action')->ids;

        $users = $this->userRepository->findUsersByIds($ids);
        $usersToGenerate = [];

        foreach ($users as $user) {
            if ($user->getPaymentDate()) {
                $usersToGenerate[] = $user;
                $user->setIncomeProofPrintedDate(new \DateTime());
                $this->userRepository->save($user);
            }
        }

        $this->pdfExportService->generatePaymentProofs($usersToGenerate);
    }

    protected function createComponentUsersGrid($name)
    {
        return $this->usersGridControlFactory->create();
    }
}