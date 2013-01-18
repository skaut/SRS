<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 18.1.13
 * Time: 12:53
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Components;

class Menu extends \Nette\Application\UI\Control
{
    /**
     * @var \Nella\Doctrine\Repository
    */
    protected $pageRepository;

    public function __construct($pageRepository) {
        $this->pageRepository = $pageRepository;
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');
        $template->pages = $this->pageRepository->findPublishedOrderedByPosition();
        $template->render();
    }

}
