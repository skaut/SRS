<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="SettingsRepository")
 * @ORM\Table(name="settings")
 */
class Settings
{
    const BASIC_BLOCK_DURATION = 'basic_block_duration';
    const SEMINAR_FROM_DATE = 'seminar_from_date';
    const SEMINAR_TO_DATE = 'seminar_to_date';
    const IS_ALLOWED_MODIFY_SCHEDULE = 'is_allowed_modify_schedule';

    /**
     * @ORM\Column(type="string", unique=true)
     * @ORM\Id
     * @var string
     */
    protected $item;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $value;

    /**
     * Settings constructor.
     * @param string $item
     * @param string $value
     */
    public function __construct($item, $value)
    {
        $this->item = $item;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param string $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}

