<?php

namespace App\Model\Settings;

use Kdyby\Doctrine\EntityRepository;

class SettingsRepository extends EntityRepository
{
    public function getValue($item)
    {
        return $this->findOneBy(array('item' => $item))->getValue();
    }
}