<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 19.1.13
 * Time: 10:37
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Components;

class FaqBox extends \Nette\Application\UI\Control
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $this->template->faq = $this->presenter->context->database->getRepository('\SRS\model\CMS\Faq')->findAllOrderedPublished();
        $template->backlink = $this->presenter->context->httpRequest->url->path;
        $template->render();
    }

    public function createComponentQuestionForm() {
        return new \SRS\Form\CMS\QuestionForm();
    }

}