<?php

declare(strict_types=1);

namespace App\Utils;

use Codeception\Test\Unit;

class HelpersTest extends Unit
{
    public function testTruncate(): void
    {
        $this->assertEquals('test…', Helpers::truncate('test a b c', 5));
        $this->assertEquals('test…', Helpers::truncate('test a b c', 6));
        $this->assertEquals('test a…', Helpers::truncate('test a b c', 7));
        $this->assertEquals('test a…', Helpers::truncate('test a b c', 8));
        $this->assertEquals('test a b…', Helpers::truncate('test a b c', 9));
        $this->assertEquals('test a b c', Helpers::truncate('test a b c', 10));
        $this->assertEquals('testa…', Helpers::truncate('testabc', 6));
    }
}
