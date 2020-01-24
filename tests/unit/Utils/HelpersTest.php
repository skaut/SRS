<?php

declare(strict_types=1);

namespace App\Utils;

use Codeception\Test\Unit;

class HelpersTest extends Unit
{
    public function testTruncate()
    {
        $this->assertEquals("test...", Helpers::truncate("test a b c", 5));
        $this->assertEquals("test a...", Helpers::truncate("test a b c", 6));
    }
}
