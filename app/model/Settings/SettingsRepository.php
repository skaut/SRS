<?php

namespace App\Model\Settings;

use App\Utils\Helpers;
use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;


/**
 * Třída spravující nastavení.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SettingsRepository extends EntityRepository
{
    /** @var Cache */
    private $cache;


    /**
     * SettingsRepository constructor.
     * @param EntityManager $em
     * @param Mapping\ClassMetadata $class
     * @param IStorage $storage
     */
    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, IStorage $storage)
    {
        parent::__construct($em, $class);
        $this->cache = new Cache($storage, 'Settings');
    }

    /**
     * Vrátí hodnotu položky.
     * @param $item
     * @return mixed
     * @throws SettingsException
     */
    public function getValue($item): ?string
    {
        $value = $this->cache->load($item);
        if ($value !== NULL)
            return $value;

        $settings = $this->findOneBy(['item' => $item]);
        if ($settings === NULL)
            throw new SettingsException("Item {$item} was not found in table Settings.");

        $value = $settings->getValue();
        $this->cache->save($item, $value);

        return $value;
    }

    /**
     * Nastavení hodnoty položky.
     * @param $item
     * @param $value
     * @throws SettingsException
     */
    public function setValue($item, $value): void
    {
        $settings = $this->findOneBy(['item' => $item]);
        if ($settings === NULL)
            throw new SettingsException("Item {$item} was not found in table Settings.");

        $settings->setValue($value);
        $this->_em->flush();

        $this->cache->save($item, $value);
    }

    /**
     * Vrátí hodnotu položky typu bool.
     * @param $item
     * @return bool|null
     * @throws SettingsException
     */
    public function getBoolValue($item): ?bool
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vrátí hodnotu položky typu datum a čas.
     * @param $item
     * @return \DateTime|null
     * @throws SettingsException
     */
    public function getDateTimeValue($item): ?\DateTime
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return new \DateTime($value);
    }

    /**
     * Vrátí hodnotu položky typu datum a čas jako text.
     * @param $item
     * @return null|string
     * @throws SettingsException
     */
    public function getDateTimeValueText($item): ?string
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return (new \DateTime($value))->format(Helpers::DATETIME_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum a čas.
     * @param $item
     * @param \DateTime|null $value
     * @throws SettingsException
     */
    public function setDateTimeValue($item, $value): void
    {
        if ($value === NULL)
            $this->setValue($item, NULL);
        else
            $this->setValue($item, $value->format(\DateTime::ISO8601));
    }

    /**
     * Vrátí hodnotu položky typu datum.
     * @param $item
     * @return null|\DateTime
     * @throws SettingsException
     */
    public function getDateValue($item): ?\DateTime
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return new \DateTime($value);
    }

    /**
     * Vrátí hodnotu položky typu datum jako text.
     * @param $item
     * @return null|string
     * @throws SettingsException
     */
    public function getDateValueText($item): ?string
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return (new \DateTime($value))->format(Helpers::DATE_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum.
     * @param $item
     * @param \DateTime|null $value
     * @throws SettingsException
     */
    public function setDateValue($item, $value): void
    {
        if ($value === NULL)
            $this->setValue($item, NULL);
        else
            $this->setValue($item, $value->format('Y-m-d'));
    }
}

