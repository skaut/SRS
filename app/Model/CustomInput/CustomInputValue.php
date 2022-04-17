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
    private ?int $id = null;

    /**
     * @param CustomInput $input Vlastní pole přihlášky.
     * @param User        $user  Uživatel
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: CustomInput::class, inversedBy: 'customInputValues', cascade: ['persist'])]
        protected CustomInput $input,
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'customInputValues', cascade: ['persist'])]
        protected User $user
    ) {
    }

    public function getId(): ?int
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
