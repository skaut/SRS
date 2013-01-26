<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 21:07
 * To change this template use File | Settings | File Templates.
 */

namespace BackModule;

class BlockPresenter extends BasePresenter
{
    /**
     * @var \SRS\Model\Program\BlockRepository
     */
    protected $blockRepo;

    public function startup() {
        parent::startup();
        $this->blockRepo = $this->context->database->getRepository('\SRS\Model\Program\Block');
    }

    public function renderList() {
        $this->blockRepo->findAll();
    }

    public function renderDetail() {

    }

    public function renderEdit($id = null) {
        if ($id != null) {
            $block = $this->blockRepo->find($id);
            if ($block == null) throw new \Nette\Application\BadRequestException('Blok s tímto ID neexistuje', 404);
        }

    }

}
