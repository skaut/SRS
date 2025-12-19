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
        $this->commandBus = $this->getModule('IntegrationTester')->grabService(CommandBus::class);
        $this->queryBus   = $this->getModule('IntegrationTester')->grabService(QueryBus::class);
    }
}