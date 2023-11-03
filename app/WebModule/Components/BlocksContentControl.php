<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;

/**
 * Komponenta obsahu s podrobnostmi o programových blocích.
 */
class BlocksContentControl extends BaseContentControl
{
    public function __construct(private readonly BlockRepository $blockRepository, private readonly CategoryRepository $categoryRepository)
    {
    }

    public function render(ContentDto $content): void
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
