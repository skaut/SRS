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
    protected $resource = 'Program';

    /**
     * @var \SRS\Model\Program\BlockRepository
     */
    protected $blockRepo;

    public function startup() {
        parent::startup();
        $this->checkPermissions('Přístup');
        $this->blockRepo = $this->context->database->getRepository('\SRS\Model\Program\Block');
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->basicBlockDuration = $this->dbsettings->get('basic_block_duration');
    }

    public function renderList() {
        $blocks = $this->blockRepo->findAll();
        $this->template->blocks = $blocks;
    }

    public function renderDetail($id) {
        $block = $this->blockRepo->find($id);
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();

        if ($block == null) throw new \Nette\Application\BadRequestException('Blok s tímto ID neexistuje', 404);

        $this->template->block = $block;
    }

    public function renderEdit($id = null) {
        if ($id != null) {
            $block = $this->blockRepo->find($id);
            if ($block == null) throw new \Nette\Application\BadRequestException('Blok s tímto ID neexistuje', 404);
            $this['blockForm']->bindEntity($block);
            $this->template->block = $block;
        }
        else if (!(bool) $this->dbsettings->get('is_allowed_add_block')) {
            $this->flashMessage('Přidávání nových bloků je zakázáno. Toto lze povolit v modulu konfigurace', 'info');
            $this->redirect(':Back:Block:list');
        }

    }

    public function handleDelete($id) {
        $block = $this->blockRepo->find($id);
        if ($block == null) throw new \Nette\Application\BadRequestException('Blok s tímto ID neexistuje', 404);
        $this->context->database->remove($block);
        $this->context->database->flush();
        $this->flashMessage('Blok smazán', 'success');
        $this->redirect(":Back:Block:list");
    }

    public function actionGet() {
        $blocks = $this->blockRepo->findAll();
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $json = $serializer->serialize($blocks, 'json');
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();
    }

    protected function createComponentBlockForm() {
        return new \SRS\Form\Program\BlockForm(null, null, $this->presenter->dbsettings, $this->context->database, $this->user);
    }

    protected function createComponentBlockGrid() {
        return new \SRS\Components\BlockGrid($this->context->database);
    }

}
