<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;
use function implode;

/**
 * Abstraktní entita vlastní pole přihlášky.
 *
 * @ORM\Entity(repositoryClass="\App\Model\CustomInput\Repositories\CustomInputRepository")
 * @ORM\Table(name="custom_input")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox" = "CustomCheckbox",
 *     "custom_text" = "CustomText",
 *     "custom_select" = "CustomSelect",
 *     "custom_multiselect" = "CustomMultiSelect",
 *     "custom_file" = "CustomFile",
 *     "custom_date" = "CustomDate",
 *     "custom_datetime" = "CustomDateTime"
 * })
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class CustomInput
{
    /**
     * Textové pole.
     */
    public const TEXT = 'text';

    /**
     * Zaškrtávací pole.
     */
    public const CHECKBOX = 'checkbox';

    /**
     * Výběrové pole.
     */
    public const SELECT = 'select';

    /**
     * Výběrové pole s více možnostmi.
     */
    public const MULTISELECT = 'multiselect';

    /**
     * Soubor.
     */
    public const FILE = 'file';

    /**
     * Datum.
     */
    public const DATE = 'date';

    /**
     * Datum čas.
     */
    public const DATETIME = 'datetime';


    /** @var string[] */
    public static array $types = [
        self::TEXT,
        self::DATE,
        self::DATETIME,
        self::CHECKBOX,
        self::SELECT,
        self::MULTISELECT,
        self::FILE,
    ];

    /**
     * Typ vlastního pole.
     */
    protected string $type;
    use Id;

    /**
     * Název vlastního pole.
     *
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * Povinné pole.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $mandatory = false;

    /**
     * Pořadí pole na přihlášce.
     *
     * @ORM\Column(type="integer")
     */
    protected int $position = 0;

    /**
     * Hodnoty pole pro jednotlivé uživatele.
     *
     * @ORM\OneToMany(targetEntity="CustomInputValue", mappedBy="input", cascade={"persist"})
     *
     * @var Collection|CustomInputValue[]
     */
    protected Collection $customInputValues;

    /**
     * Role, pro které se pole zobrazuje.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role")
     *
     * @var Collection|Role[]
     */
    protected Collection $roles;

    public function __construct()
    {
        $this->customInputValues = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function isMandatory() : bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory) : void
    {
        $this->mandatory = $mandatory;
    }

    public function getPosition() : int
    {
        return $this->position;
    }

    public function setPosition(int $position) : void
    {
        $this->position = $position;
    }

    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return Collection|CustomInputValue[]
     */
    public function getCustomInputValues() : Collection
    {
        return $this->customInputValues;
    }

    /**
     * @return Role[]|Collection
     */
    public function getRoles() : Collection
    {
        return $this->roles;
    }

    /**
     * @param Role[]|Collection $roles
     */
    public function setRoles(Collection $roles) : void
    {
        $this->roles = $roles;
    }

    public function getRolesText() : string
    {
        return implode(', ', $this->roles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }
}
