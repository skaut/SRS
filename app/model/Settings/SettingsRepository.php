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

    public function getDateValue($item)
    {
        return new \DateTime($this->getValue($item));
    }

    public function getDateTimeValue($item)
    {
        return new \DateTime($this->getValue($item));
    }

    public function setValue($item, $value)
    {
        $setting = $this->findOneBy(['item' => $item]);
        if ($setting === null)
            throw new SettingsException("Item {$item} was not found in table Settings.");
        $setting->setValue($value);
    }

    public function setDateValue($item, $value)
    {
        //TODO
    }

    public function setDateTimeValue($item, $value)
    {
        //TODO
    }
}

