<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Program\Category;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\ISettingsService;
use CommandHandlerTest;

final class SaveCategoryHandlerTest extends CommandHandlerTest
{
    private ISettingsService $settingsService;

    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    public function testSaveBlock(): void
    {
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
        return [Category::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/SaveCategoryHandlerTest.neon']);
        parent::_before();

        $this->settingsService              = $this->tester->grabService(ISettingsService::class);
        $this->blockRepository              = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->categoryRepository           = $this->tester->grabService(CategoryRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->programRepository            = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);

        $this->settingsService->setBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, false);
        $this->settingsService->setValue(Settings::SEMINAR_NAME, 'test');
    }
}
