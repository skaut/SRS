<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Localization\Translator;

class TranslatorStub implements Translator
{
    function translate($message, ...$parameters): string
    {
        return "";
    }
}