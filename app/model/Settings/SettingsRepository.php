<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;


class SettingsRepository extends EntityRepository
{
    /** @var Cache */
    private $cache;


    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, IStorage $storage)
    {
        parent::__construct($em, $class);
        $this->cache = new Cache($storage, 'Settings');
    }

    /**
     * @param $item
     * @return mixed
     * @throws SettingsException
     */
    public function getValue($item)
    {
        $value = $this->cache->load($item);
        if ($value !== null)
            return $value;

        $settings = $this->findOneBy(['item' => $item]);
        if ($settings === null)
            throw new SettingsException("Item {$item} was not found in table Settings.");

        $value = $settings->getValue();
        $this->cache->save($item, $value);

        return $value;
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
        $settings = $this->findOneBy(['item' => $item]);
        if ($settings === null)
            throw new SettingsException("Item {$item} was not found in table Settings.");

        $settings->setValue($value);
        $this->_em->flush();

        $this->cache->save($item, $value);
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
}

