<?php

namespace App\Model\Settings;

use Kdyby\Doctrine\EntityRepository;

class SettingsRepository extends EntityRepository
{
    public function getValue($item)
    {
        $setting = $this->findOneBy(['item' => $item]);
        if ($setting === null)
            throw new SettingsException("Item {$item} was not found in table Settings.");
        return $setting->getValue();
    }

    public function setValue($item, $value)
    {
        $setting = $this->findOneBy(['item' => $item]);
        if ($setting === null)
            throw new SettingsException("Item {$item} was not found in table Settings.");
        $setting->setValue($value);
    }
}

class SettingsException extends \Exception
{

}