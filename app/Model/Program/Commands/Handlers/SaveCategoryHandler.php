<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\SaveCategory;
use App\Model\Program\Events\CategoryUpdatedEvent;
use App\Model\Program\Repositories\CategoryRepository;
use App\Services\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveCategoryHandler implements MessageHandlerInterface
{
    public function __construct(
        private EventBus $eventBus,
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepository
    ) {
    }

    public function __invoke(SaveCategory $command): void
    {
        $category    = $command->getCategory();
        $categoryOld = $command->getCategoryOld();

        if ($category->getId() === null) {
            $this->categoryRepository->save($category);
        } else {
            $this->em->wrapInTransaction(function () use ($category, $categoryOld): void {
                $registerableRolesOld = $categoryOld->getRegisterableRoles();

                $this->categoryRepository->save($category);

                $this->eventBus->handle(new CategoryUpdatedEvent($category, $registerableRolesOld));
            });
        }
    }
}
