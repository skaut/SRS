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

    protected $basicBlockDuration;

    public function startup() {
        parent::startup();
        $this->programRepo = $this->context->database->getRepository('\SRS\Model\Program\Program');
        $this->basicBlockDuration = $this->dbsettings->get('basic_block_duration');
    }

    public function renderDefault() {
//        $blocks = $this->blockRepo->findAll();
//        $this->template->blocks = $blocks;
    }


    public function actionGet() {
        $programs = $this->programRepo->findAllForJson($this->basicBlockDuration);
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $json = $serializer->serialize($programs, 'json');
        $response = new \Nette\Application\Responses\TextResponse($json);
        $this->sendResponse($response);
        $this->terminate();
    }


    public function actionSet($data) {
        $program = $this->programRepo->saveFromJson($data, $this->basicBlockDuration);
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
            $result[$block->id] = array('id' => $block->id, 'name' => $block->name, 'tools' => $block->tools, 'location' => $block->location,  'capacity' => $block->capacity, 'duration' => $block->duration);
        }
        $response = new \Nette\Application\Responses\JsonResponse($result);
        $this->sendResponse($response);
        $this->terminate();

    }

    public function actionGetCalendarConfig()
    {
        $calConfig = array();
        $fromDate = $this->dbsettings->get('seminar_from_date');
        $datePieces = explode('-', $fromDate);
        $calConfig['year'] = $datePieces[0];
        $calConfig['month'] = $datePieces[1]-1; //fullcalendar je zerobased
        $calConfig['date'] = $datePieces[2];
        $calConfig['basic_block_duration'] = $this->dbsettings->get('basic_block_duration');

        $response = new \Nette\Application\Responses\JsonResponse($calConfig);
        $this->sendResponse($response);
        $this->terminate();

    }
}

