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
        $programs = $this->programRepo->findAllForJson($this->dbsettings->get('basic_block_duration'));
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $json = $serializer->serialize($programs, 'json');
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();
    }


    public function actionSet($data) {

        //$serializer = \JMS\Serializer\SerializerBuilder::create()->build();
         $data = json_decode($data);
         $data = (array) $data;

        $exists = isset($data['id']);
        if ($exists == true) {
        $program = $this->programRepo->find($data['id']);
        }
        else {
            $program = new \SRS\Model\Program\Program();
            $program->duration = 1; //TODO docasne
        }


        $program->setProperties($data, $this->context->database);

        $this->context->database->persist($program);
        $this->context->database->flush();
        $response = new \Nette\Application\Responses\JsonResponse(array('id' => $program->id));
        $this->sendResponse($response);
        $this->terminate();
    }

    public function actionDelete($id) {

        $program = $this->programRepo->find($id);
        if ($program != null) {
            $this->context->database->remove($program);
            $this->context->database->flush();
            $response = new \Nette\Application\Responses\JsonResponse(array('status' => 'ok'));
        }
        else {
            $response = new \Nette\Application\Responses\JsonResponse(array('status' => 'error'));
        }
        $this->sendResponse($response);
        $this->terminate();

    }


    public function actionGetOptions() {
        $blocks = $this->context->database->getRepository('\SRS\Model\Program\Block')->findAll();
        $result = array();

        foreach ($blocks as $block) {
            $result[$block->id] = array('id' => $block->id, 'name' => $block->name);
        }
        $response = new \Nette\Application\Responses\JsonResponse($result);
        $this->sendResponse($response);
        $this->terminate();

    }
}

