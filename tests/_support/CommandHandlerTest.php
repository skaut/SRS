<?php

declare(strict_types=1);

use App\Services\CommandBus;
use App\Services\QueryBus;

abstract class CommandHandlerTest extends IntegrationTest
{
    protected CommandBus $commandBus;

    protected QueryBus $queryBus;

    protected function _before() : void
    {
        parent::_before();
        $tester           = new IntegrationTester($this->getScenario());
        $this->commandBus = $tester->grabService(CommandBus::class);
        $this->queryBus   = $tester->grabService(QueryBus::class);
    }
}