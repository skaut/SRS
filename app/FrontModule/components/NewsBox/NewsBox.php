<?php
/**
 * Date: 19.1.13
 * Time: 10:37
 * Author: Michal Májský
 */
namespace SRS\Components;

/**
 * Komponenta pro vypis novinek na FE
 */
class NewsBox extends \Nette\Application\UI\Control
{

    public function render($contentID)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');
        $content = $this->presenter->context->database->getRepository('\SRS\Model\CMS\NewsContent')->find($contentID);

        $this->template->news = $this->presenter->context->database->getRepository('\SRS\model\CMS\News')->findAllOrderedByDate($content->count);

        $template->render();
    }


}