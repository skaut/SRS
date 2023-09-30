<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use function implode;

/**
 * Abstraktní entita vlastní pole přihlášky.
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_input')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'custom_checkbox' => CustomCheckbox::class,
    'custom_text' => CustomText::class,
    'custom_select' => CustomSelect::class,
    'custom_multiselect' => CustomMultiSelect::class,
    'custom_file' => CustomFile::class,
    'custom_date' => CustomDate::class,
    'custom_datetime' => CustomDateTime::class,
])]
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Název vlastního pole.
     */
    #[ORM\Column(type: 'string')]
    protected string $name;

    /**
     * Povinné pole.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $mandatory = false;

    /**
     * Pořadí pole na přihlášce.
     */
    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    /**
     * Hodnoty pole pro jednotlivé uživatele.
     *
     * @var Collection<int, CustomInputValue>
     */
    #[ORM\OneToMany(mappedBy: 'input', targetEntity: CustomInputValue::class, cascade: ['persist'])]
    protected Collection $customInputValues;

    /**
     * Role, pro které se pole zobrazuje.
     *
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    protected Collection $roles;

    public function __construct()
    {
        $this->customInputValues = new ArrayCollection();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): void
    {
        $this->mandatory = $mandatory;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /** @return Collection<int, CustomInputValue> */
    public function getCustomInputValues(): Collection
    {
        return $this->customInputValues;
    }

    public function addCustomInputValue(CustomInputValue $value): void
    {
        if (! $this->customInputValues->contains($value)) {
            $this->customInputValues->add($value);
        }
    }

    /** @return Collection<int, Role> */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /** @param Collection<int, Role> $roles */
    public function setRoles(Collection $roles): void
    {
        $this->roles = $roles;
    }

    public function getRolesText(): string
    {
        return implode(', ', $this->roles->map(static fn (Role $role) => $role->getName())->toArray());
    }
}
