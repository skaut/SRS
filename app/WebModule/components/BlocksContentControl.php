<?php
declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use Nette\Application\UI\Control;


/**
 * Komponenta s podrobnostmi o programových blocích.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlocksContentControl extends Control
{
    /** @var BlockRepository */
    private $blockRepository;

    /** @var CategoryRepository */
    private $categoryRepository;


    /**
     * BlocksContentControl constructor.
     * @param BlockRepository $blockRepository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(BlockRepository $blockRepository, CategoryRepository $categoryRepository)
    {
        parent::__construct();

        $this->blockRepository = $blockRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param $content
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/blocks_content.latte');

        $template->heading = $content->getHeading();
        $template->categories = $this->categoryRepository->findAllOrderedByName();
        $template->allBlocks = $this->blockRepository->findAllOrderedByName();
        $template->uncategorizedBlocks = $this->blockRepository->findAllUncategorizedOrderedByName();

        $selectedBlockId = $this->getPresenter()->getParameter('blockId');

        if ($selectedBlockId != NULL) {
            $selectedBlock = $this->blockRepository->findById($selectedBlockId);
            $this->template->selectedBlockId = $selectedBlockId;
            $this->template->selectedBlock = $selectedBlock;
            if ($selectedBlock->getCategory())
                $this->template->selectedCategoryId = $selectedBlock->getCategory()->getId();
            else
                $this->template->selectedCategoryId = 'uncategorized';
        } else {
            $this->template->selectedBlockId = NULL;
            $this->template->selectedCategoryId = NULL;
        }

        $template->render();
    }
}
