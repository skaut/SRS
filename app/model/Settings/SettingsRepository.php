<?php

namespace App\Model\Settings;

use Kdyby\Doctrine\EntityRepository;

class SettingsRepository extends EntityRepository
{
    //TODO cachovani

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
        $this->_em->flush();
    }

    public function setDateValue($item, $value)
    {
        $this->setValue($item, $value->format('Y-m-d'));
    }

    public function setDateTimeValue($item, $value)
    {
        $this->setValue($item, $value->format(\DateTime::ISO8601));
    }
}

