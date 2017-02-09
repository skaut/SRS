<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;

class SettingsRepository extends EntityRepository
{
    /** @var Translator */
    private $translator;

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, Translator $translator)
    {
        parent::__construct($em, $class);
        $this->translator = $translator;
    }

    /**
     * @param $item
     * @return mixed
     * @throws SettingsException
     */
    public function getValue($item)
    {
        $setting = $this->findOneBy(['item' => $item]); //TODO cachovani
        if ($setting === null)
            throw new SettingsException("Item {$item} was not found in table Settings.");
        return $setting->getValue();
    }

    /**
     * @param $item
     * @return \DateTime
     */
    public function getDateValue($item)
    {
        return new \DateTime($this->getValue($item));
    }

    /**
     * @param $item
     * @return \DateTime
     */
    public function getDateTimeValue($item)
    {
        return new \DateTime($this->getValue($item));
    }

    /**
     * @param $item
     * @param $value
     * @throws SettingsException
     */
    public function setValue($item, $value)
    {
        $setting = $this->findOneBy(['item' => $item]);
        if ($setting === null)
            throw new SettingsException("Item {$item} was not found in table Settings.");
        $setting->setValue($value);
        $this->_em->flush();
    }

    /**
     * @param $item
     * @param \DateTime $value
     */
    public function setDateValue($item, \DateTime $value)
    {
        $this->setValue($item, $value->format('Y-m-d'));
    }

    /**
     * @param $item
     * @param \DateTime $value
     */
    public function setDateTimeValue($item, \DateTime $value)
    {
        $this->setValue($item, $value->format(\DateTime::ISO8601));
    }

    /**
     * @return array
     */
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

