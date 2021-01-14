<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Acl\Events\CategoryUpdatedEvent;
use App\Model\Program\Commands\SaveCategory;
use App\Model\Program\Repositories\CategoryRepository;
use App\Services\EventBus;
use Nettrine\ORM\EntityManagerDecorator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveCategoryHandler implements MessageHandlerInterface
{
    private EventBus $eventBus;

    private EntityManagerDecorator $em;

    private CategoryRepository $categoryRepository;

    public function __construct(EventBus $eventBus, EntityManagerDecorator $em, CategoryRepository $categoryRepository)
    {
        $this->eventBus           = $eventBus;
        $this->em                 = $em;
        $this->categoryRepository = $categoryRepository;
    }

    public function __invoke(SaveCategory $command): void
    {
        $category = $command->getCategory();

        if ($category->getId() === null) {
            $this->categoryRepository->save($category);
        } else {
            $this->em->transactional(function () use ($category): void {
                $originalCategory = $this->categoryRepository->findById($category->getId());

                $originalRegisterableRoles = clone $originalCategory->getRegisterableRoles();

                $this->categoryRepository->save($category);

                $this->eventBus->handle(new CategoryUpdatedEvent($category, $originalRegisterableRoles));
            });
        }
    }
}
