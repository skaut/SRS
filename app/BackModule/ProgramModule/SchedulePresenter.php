<?php
/**
 * Date: 26.1.13
 * Time: 21:07
 * Author: Michal Májský
 */

namespace BackModule\ProgramModule;
use SRS\Model\Acl\Resource;
use SRS\Model\Acl\Permission;

class SchedulePresenter extends \BackModule\BasePresenter
{

    protected $resource = Resource::PROGRAM;


    /**
     * @var \SRS\Model\Program\ProgramRepository
     */
    protected $programRepo;

    protected $basicBlockDuration;

    public function startup()
    {
        parent::startup();
        $this->checkPermissions(Permission::ACCESS);
        $this->programRepo = $this->context->database->getRepository('\SRS\Model\Program\Program');
        $this->basicBlockDuration = $this->dbsettings->get('basic_block_duration');
    }

    public function renderDefault()
    {

    }


}

