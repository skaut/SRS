<?php
/**
 * Date: 18.1.13
 * Time: 12:53
 * Author: Michal Májský
 */
namespace SRS\Components;

class Menu extends \Nette\Application\UI\Control
{
    /**
     * @var \Nella\Doctrine\Repository
     */
    protected $pageRepository;

    public function __construct($pageRepository)
    {
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
