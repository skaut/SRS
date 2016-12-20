<?php

namespace App\Services;

use Nette\Utils\Neon;

class ConfigFacade
{
    private $config;

    public function getConfig() {
        if ($this->config === null)
            $this->config = Neon::decode(file_get_contents(__DIR__ . '/../config/config.local.neon'));
        return $this->config;
    }

}