<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Abstraktní entita hodnota vlastního pole přihlášky.
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_input_value')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'custom_checkbox_value' => CustomCheckboxValue::class,
    'custom_text_value' => CustomTextValue::class,
    'custom_select_value' => CustomSelectValue::class,
    'custom_multiselect_value' => CustomMultiSelectValue::class,
    'custom_file_value' => CustomFileValue::class,
    'custom_date_value' => CustomDateValue::class,
    'custom_datetime_value' => CustomDateTimeValue::class,
])]
abstract class CustomInputValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Vlastní pole přihlášky.
     */
    #[ORM\ManyToOne(targetEntity: CustomInput::class, inversedBy: 'customInputValues', cascade: ['persist'])]
    protected CustomInput $input;

    /**
     * Uživatel.
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'customInputValues', cascade: ['persist'])]
    protected User $user;

    public function __construct(CustomInput $input, User $user)
    {
        $this->input = $input;
        $this->user  = $user;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getInput(): CustomInput
    {
        return $this->input;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    abstract public function getValueText(): string;
}
