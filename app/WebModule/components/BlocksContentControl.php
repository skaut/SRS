<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\CMS\Content\ContentDTO;
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

    public function __construct(BlockRepository $blockRepository, CategoryRepository $categoryRepository)
    {
        parent::__construct();

        $this->blockRepository    = $blockRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function render(ContentDTO $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/blocks_content.latte');

        $template->heading             = $content->getHeading();
        $template->categories          = $this->categoryRepository->findAllOrderedByName();
        $template->allBlocks           = $this->blockRepository->findAllOrderedByName();
        $template->uncategorizedBlocks = $this->blockRepository->findAllUncategorizedOrderedByName();

        $selectedBlockId = $this->getPresenter()->getParameter('blockId');

        if ($selectedBlockId !== null) {
            $selectedBlock                   = $this->blockRepository->findById((int) $selectedBlockId);
            $this->template->selectedBlockId = $selectedBlockId;
            $this->template->selectedBlock   = $selectedBlock;
            if ($selectedBlock->getCategory()) {
                $this->template->selectedCategoryId = $selectedBlock->getCategory()->getId();
            } else {
                $this->template->selectedCategoryId = 'uncategorized';
            }
        } else {
            $this->template->selectedBlockId    = null;
            $this->template->selectedCategoryId = null;
        }

        $template->render();
    }
}
