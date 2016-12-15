<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="SettingsRepository")
 * @ORM\Table(name="settings")
 */
class Settings
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @ORM\Id
     */
    protected $item;

    /** @ORM\Column(type="string", nullable=true) */
    protected $value;

    /**
     * Settings constructor.
     * @param $item
     * @param $value
     */
    public function __construct($item, $value)
    {
        $this->item = $item;
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param mixed $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}