<?php

namespace App\Model\Settings\CustomInput;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Abstraktní entita vlastní pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="CustomInputRepository")
 * @ORM\Table(name="custom_input")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox" = "CustomCheckbox",
 *     "custom_text" = "CustomText",
 *     "custom_select" = "CustomSelect",
 *     "custom_file" = "CustomFile"
 * })
 */
abstract class CustomInput
{
    /**
     * Textové pole.
     */
    const TEXT = 'text';

    /**
     * Zaškrtávací pole.
     */
    const CHECKBOX = 'checkbox';

    /**
     * Výběrové pole.
     */
    const SELECT = 'select';

    /**
     * Soubor.
     */
    const FILE = 'file';

    public static $types = [
        self::TEXT,
        self::CHECKBOX,
        self::SELECT,
        self::FILE
    ];

    /**
     * Typ vlastního pole.
     */
    protected $type;

    use Identifier;

    /**
     * Název vlastního pole.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Povinné pole.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $mandatory = FALSE;

    /**
     * Pořadí pole na přihlášce.
     * @ORM\Column(type="integer")
     * @var integer
     */
    protected $position;

    /**
     * Hodnoty pole pro jednotlivé uživatele.
     * @ORM\OneToMany(targetEntity="\App\Model\User\CustomInputValue\CustomInputValue", mappedBy="input", cascade={"persist"})
     * @var Collection
     */
    protected $customInputValues;


    /**
     * CustomInput constructor.
     */
    public function __construct()
    {
        $this->customInputValues = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param bool $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Collection
     */
    public function getCustomInputValues()
    {
        return $this->customInputValues;
    }

    /**
     * @param Collection $customInputValues
     */
    public function setCustomInputValues($customInputValues)
    {
        $this->customInputValues = $customInputValues;
    }
}
