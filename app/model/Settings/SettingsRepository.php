<?php
declare(strict_types=1);

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
     * @param string $item
     * @return mixed
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getValue(string $item): ?string
    {
        $value = $this->cache->load($item);

        if ($value === NULL) {
            $settings = $this->findOneBy(['item' => $item]);
            if ($settings === NULL)
                throw new SettingsException("Item {$item} was not found in table Settings.");

            $value = $settings->getValue();
            $this->cache->save($item, $value);
        }

        return $value;
    }

    /**
     * Nastaví hodnotu položky.
     * @param string $item
     * @param null|string $value
     * @throws SettingsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
     */
    public function setValue(string $item, ?string $value): void
    {
        $settings = $this->findOneBy(['item' => $item]);
        if ($settings === NULL)
            throw new SettingsException("Item {$item} was not found in table Settings.");

        $settings->setValue($value);
        $this->_em->flush();

        $this->cache->save($item, $value);
    }

    /**
     * Vrátí hodnotu položky typu int.
     * @param string $item
     * @return int|null
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getIntValue(string $item): ?int
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * Nastaví hodnotu položky typu int.
     * @param string $item
     * @param int|null $value
     * @throws SettingsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
     */
    public function setIntValue(string $item, ?int $value): void
    {
        $this->setValue($item, (string) $value);
    }

    /**
     * Vrátí hodnotu položky typu bool.
     * @param string $item
     * @return bool|null
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getBoolValue(string $item): ?bool
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Nastaví hodnotu položky typu bool.
     * @param string $item
     * @param bool|null $value
     * @throws SettingsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
     */
    public function setBoolValue(string $item, ?bool $value): void
    {
        $this->setValue($item, (string) $value);
    }

    /**
     * Vrátí hodnotu položky typu datum a čas.
     * @param string $item
     * @return \DateTime|null
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getDateTimeValue(string $item): ?\DateTime
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return new \DateTime($value);
    }

    /**
     * Vrátí hodnotu položky typu datum a čas jako text.
     * @param string $item
     * @return null|string
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getDateTimeValueText(string $item): ?string
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return (new \DateTime($value))->format(Helpers::DATETIME_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum a čas.
     * @param string $item
     * @param \DateTime|null $value
     * @throws SettingsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
     */
    public function setDateTimeValue(string $item, ?\DateTime $value): void
    {
        if ($value === NULL)
            $this->setValue($item, NULL);
        else
            $this->setValue($item, $value->format(\DateTime::ISO8601));
    }

    /**
     * Vrátí hodnotu položky typu datum.
     * @param string $item
     * @return null|\DateTime
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getDateValue(string $item): ?\DateTime
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return new \DateTime($value);
    }

    /**
     * Vrátí hodnotu položky typu datum jako text.
     * @param string $item
     * @return null|string
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getDateValueText(string $item): ?string
    {
        $value = $this->getValue($item);
        if ($value === NULL)
            return NULL;
        return (new \DateTime($value))->format(Helpers::DATE_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum.
     * @param string $item
     * @param \DateTime|null $value
     * @throws SettingsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
     */
    public function setDateValue(string $item, ?\DateTime $value): void
    {
        if ($value === NULL)
            $this->setValue($item, NULL);
        else
            $this->setValue($item, $value->format('Y-m-d'));
    }
}

