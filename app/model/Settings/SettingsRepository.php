<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;

class SettingsRepository extends EntityRepository
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, Translator $translator)
    {
        parent::__construct($em, $class);
        $this->translator = $translator;
    }

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

    public function getDurationsOptions() {
        $MAX_LENGTH = 240;

        $basicBlockDuration = $this->getValue('basic_block_duration');
        $options = [];
        for ($i = 1; $basicBlockDuration * $i <= $MAX_LENGTH; $i++) {
            $options[$i] = $this->translator->translate('admin.common.minutes', null, ['count' => $basicBlockDuration * $i]);
        }
        return $options;
    }
}

