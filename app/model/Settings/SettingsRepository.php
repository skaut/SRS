<?php

namespace App\Model\Settings;

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
    public function getValue($item)
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
     * Vrátí hodnotu položky typu datum a čas.
     * @param $item
     * @return \DateTime
     * @throws SettingsException
     */
    public function getDateTimeValue($item)
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return new \DateTime($value);
    }

    /**
     * Nastavení hodnoty položky typu datum.
     * @param $item
     * @param \DateTime|null $value
     * @throws SettingsException
     */
    public function setDateValue($item, $value)
    {
        if ($value === NULL)
            $this->setValue($item, NULL);
        else
            $this->setValue($item, $value->format('Y-m-d'));
    }

    /**
     * Nastavení hodnoty položky.
     * @param $item
     * @param $value
     * @throws SettingsException
     */
    public function setValue($item, $value)
    {
        $settings = $this->findOneBy(['item' => $item]);
        if ($settings === NULL)
            throw new SettingsException("Item {$item} was not found in table Settings.");

        $settings->setValue($value);
        $this->_em->flush();

        $this->cache->save($item, $value);
    }

    /**
     * Vrátí hodnotu položky typu datum.
     * @param $item
     * @return \DateTime
     * @throws SettingsException
     */
    public function getDateValue($item)
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return new \DateTime($value);
    }

    /**
     * Nastavení hodnoty položky typu datum a čas.
     * @param $item
     * @param \DateTime|null $value
     * @throws SettingsException
     */
    public function setDateTimeValue($item, $value)
    {
        if ($value === NULL)
            $this->setValue($item, NULL);
        else
            $this->setValue($item, $value->format(\DateTime::ISO8601));
    }
}

