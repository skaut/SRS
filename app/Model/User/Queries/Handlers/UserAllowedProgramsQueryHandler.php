<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Program\Program;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class UserAllowedProgramsQueryHandler
{
    private CategoryRepository $categoryRepository;

    private ProgramRepository $programRepository;

    public function __construct(CategoryRepository $categoryRepository, ProgramRepository $programRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->programRepository  = $programRepository;
    }

    /**
     * @return Collection<Program>
     */
    public function __invoke(UserAllowedProgramsQuery $query) : Collection
    {
        if (! $query->getUser()->isAllowed(SrsResource::PROGRAM, Permission::CHOOSE_PROGRAMS)) {
            return new ArrayCollection();
        }

        $allowedCategories = $this->categoryRepository->findUserAllowed($query->getUser());
        $allowedSubevents  = $query->getUser()->getSubevents();

        return $this->programRepository->findAllowedForCategoriesAndSubevents($allowedCategories, $allowedSubevents);
    }
}
