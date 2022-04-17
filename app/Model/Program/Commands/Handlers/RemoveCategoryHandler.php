<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveCategory;
use App\Model\Program\Commands\SaveBlock;
use App\Model\Program\Repositories\CategoryRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveCategoryHandler implements MessageHandlerInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepository
    ) {
    }

    public function __invoke(RemoveCategory $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $category = $command->getCategory();

            foreach ($category->getBlocks() as $block) {
                $blockOld = clone $block;
                $block->setCategory(null);
                $this->commandBus->handle(new SaveBlock($block, $blockOld));
            }

            $this->categoryRepository->remove($category);
        });
    }
}
