<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveCategory;
use App\Model\Program\Repositories\CategoryRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveCategoryHandler implements MessageHandlerInterface
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function __invoke(RemoveCategory $command): void
    {
        $this->categoryRepository->remove($command->getCategory());
    }
}
