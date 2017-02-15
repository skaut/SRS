<?php

namespace App\Model\Settings\CustomInput;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * @ORM\Entity(repositoryClass="CustomInputRepository")
 * @ORM\Table(name="custom_input")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox" = "CustomCheckbox",
 *     "custom_text" = "CustomText"
 * })
 */
abstract class CustomInput
{
    const CHECKBOX = 'checkbox';
    const TEXT = 'text';

    public static $types = [
        self::CHECKBOX,
        self::TEXT
    ];

    protected $type;

    use Identifier;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    protected $position;

    /**
     * @ORM\OneToMany(targetEntity="\App\Model\User\CustomInputValue\CustomInputValue", mappedBy="input", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $customInputValues;

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
     * @return ArrayCollection
     */
    public function getCustomInputValues()
    {
        return $this->customInputValues;
    }

    /**
     * @param ArrayCollection $customInputValues
     */
    public function setCustomInputValues($customInputValues)
    {
        $this->customInputValues = $customInputValues;
    }
}