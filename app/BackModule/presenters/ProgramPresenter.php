<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 21:07
 * To change this template use File | Settings | File Templates.
 */

namespace BackModule;

class ProgramPresenter extends BasePresenter
{
    /**
     * @var \SRS\Model\Program\ProgramRepository
     */
    protected $programRepo;

    public function startup() {
        parent::startup();
        $this->programRepo = $this->context->database->getRepository('\SRS\Model\Program\Program');
    }

    public function renderDefault() {
//        $blocks = $this->blockRepo->findAll();
//        $this->template->blocks = $blocks;
    }


    public function actionGet() {
        $programs = $this->programRepo->findAll();
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $json = $serializer->serialize($programs, 'json');
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();
    }
}

