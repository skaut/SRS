<?php
namespace App;

use Nette\Utils\Neon;

class ConfigWriter
{
    public function getConfig() {
        return Neon::decode(file_get_contents(__DIR__ . '/../config/config.local.neon'));
    }

    public function saveConfig($config) {
        $configFile = Neon::encode($config, Neon::BLOCK);
        return \file_put_contents(__DIR__ . '/../config/config.local.neon', $configFile);
    }
}