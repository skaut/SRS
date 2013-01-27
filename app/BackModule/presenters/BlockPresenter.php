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
        $blocks = $this->blockRepo->findAll();
        $this->template->blocks = $blocks;
    }

    public function renderDetail($id) {
        $block = $this->blockRepo->find($id);
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        \Nette\Diagnostics\Debugger::dump('ahoj ahoj');
        \Nette\Diagnostics\Debugger::dump($json = $serializer->serialize($block, 'json'));
        \Nette\Diagnostics\Debugger::dump($serializer->deserialize($json,'SRS\Model\Program\Block', 'json'));
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

    }

    public function handleDelete($id) {
        $block = $this->blockRepo->find($id);
        if ($block == null) throw new \Nette\Application\BadRequestException('Blok s tímto ID neexistuje', 404);
        $this->context->database->remove($block);
        $this->context->database->flush();
        $this->flashMessage('Blok smazán', 'success');
        $this->redirect(":Back:Block:list");
    }

    protected function createComponentBlockForm() {
        return new \SRS\Form\Program\BlockForm(null, null, $this->presenter->dbsettings, $this->context->database);
    }

}
