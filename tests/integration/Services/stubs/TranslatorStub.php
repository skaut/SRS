<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Localization\Translator;

class TranslatorStub implements Translator
{
    /**
     * @param string $message
     * @param mixed  ...$parameters
     */
    public function translate($message, ...$parameters): string
    {
        return $message;
    }
}
