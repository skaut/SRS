<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Category;
use App\Model\Program\Repositories\BlockRepository;
use CommandHandlerTest;

final class SaveCategoryHandlerTest extends CommandHandlerTest
{
    private BlockRepository $blockRepository;

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
        $this->blockRepository = $this->tester->grabService(BlockRepository::class);
    }
}
