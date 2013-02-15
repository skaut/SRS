<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 21:07
 * To change this template use File | Settings | File Templates.
 */

namespace BackModule;

class EvidencePresenter extends BasePresenter
{
    protected $resource = 'Evidence';

    /**
     * @var \SRS\Model\Program\BlockRepository
     */
    protected $blockRepo;

    public function startup() {
        parent::startup();
       // $this->checkPermissions('Přístup');
        //$this->blockRepo = $this->context->database->getRepository('\SRS\Model\Program\Block');
    }

    public function beforeRender() {
        parent::beforeRender();

    }

    public function renderList() {
        $blocks = $this->blockRepo->findAll();
        $this->template->blocks = $blocks;
    }


}
