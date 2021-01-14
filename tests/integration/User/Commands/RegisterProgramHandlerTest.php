<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use CommandHandlerTest;
use DateTimeImmutable;

final class RegisterProgramHandlerTest extends CommandHandlerTest
{
    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    public function testSaveBlock(): void
    {
        $subevent = new Subevent();
        $this->subeventRepository->save($subevent);

        $category = new Category("category");
        $this->categoryRepository->save($category);

        $blockAlternates = new Block("block-alternates", 60, 1, true, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($blockAlternates);

        $blockNoAlternates = new Block("block-no-alternates", 60, 1, false, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($blockNoAlternates);

        $blockCategory = new Block("block-category", 60, null, false, ProgramMandatoryType::VOLUNTARY, $subevent, $category);
        $this->blockRepository->save($blockCategory);

        $programBlockAlternates = new Program($blockAlternates, null, new DateTimeImmutable());
        $this->programRepository->save($programBlockAlternates);

        $programBlockNoAlternates = new Program($blockNoAlternates, null, new DateTimeImmutable());
        $this->programRepository->save($programBlockNoAlternates);

        $programBlockCategory = new Program($blockCategory, null, new DateTimeImmutable());
        $this->programRepository->save($programBlockCategory);

        $roleCategory = new Role("role-category");
        $roleCategory->addRegisterableCategory($category);
        $this->roleRepository->save($roleCategory);

        $roleNoCategory = new Role("role-no-category");
        $this->roleRepository->save($roleNoCategory);

        $userRoleCategory = new User();
        $userRoleCategory->addRole($roleCategory);
        $this->userRepository->save($userRoleCategory);

        $userRoleNoCategory = new User();
        $userRoleNoCategory->addRole($roleNoCategory);
        $this->userRepository->save($userRoleNoCategory);

        $userNoRole = new User();
        $this->userRepository->save($userNoRole);

        $this->commandBus->handle(new RegisterProgram($userRoleCategory, $programBlockAlternates, false));
        $this->commandBus->handle(new RegisterProgram($userRoleNoCategory, $programBlockAlternates, false));





//        $sourceCashbookId = CashbookId::fromString(self::SOURCE_CASHBOOK_ID);
//        $targetCashbookId = CashbookId::fromString(self::TARGET_CASHBOOK_ID);
//
//        $type = CashbookType::get(CashbookType::EVENT);
//        $this->cashbooks->save(new Cashbook($targetCashbookId, $type));
//        $sourceCashbook = new Cashbook($sourceCashbookId, $type);
//        $categoryId     = 123;
//        $category       = Helpers::mockChitItemCategory($categoryId);
//
//        for ($i = 0; $i < 3; $i++) {
//            $sourceCashbook->addChit(
//                new Cashbook\ChitBody(null, new Date(), null),
//                Cashbook\PaymentMethod::get(Cashbook\PaymentMethod::CASH),
//                [new Cashbook\ChitItem(new Amount('100'), $category, 'test')],
//                Helpers::mockCashbookCategories($categoryId)
//            );
//        }
//
//        // https://github.com/skaut/Skautske-hospodareni/issues/1478
//        $this->cashbooks->save($sourceCashbook);
//        $this->commandBus->handle(
//            new AddChitScan(
//                $sourceCashbookId,
//                1,
//                'foo.jpg',
//                Image::fromBlank(1, 1)->toString(),
//            )
//        );
//
//        $this->commandBus->handle(
//            new MoveChitsToDifferentCashbook([1, 3], $sourceCashbookId, $targetCashbookId)
//        );
//
//        $this->entityManager->clear();
//
//        $sourceCashbook = $this->cashbooks->find($sourceCashbookId);
//        $targetCashbook = $this->cashbooks->find($targetCashbookId);
//
//        $this->assertCount(1, $sourceCashbook->getChits());
//        $this->assertCount(2, $targetCashbook->getChits());
//        $this->assertCount(1, $targetCashbook->getChits()[0]->getScans());
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [Block::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RegisterProgramHandlerTest.neon']);
        parent::_before();

        $this->blockRepository = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository = $this->tester->grabService(UserRepository::class);
        $this->categoryRepository = $this->tester->grabService(CategoryRepository::class);
        $this->roleRepository = $this->tester->grabService(RoleRepository::class);
        $this->programRepository = $this->tester->grabService(ProgramRepository::class);
    }
}