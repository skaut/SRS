<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Abstraktní entita hodnota vlastního pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_input_value")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox_value" = "CustomCheckboxValue",
 *     "custom_text_value" = "CustomTextValue",
 *     "custom_select_value" = "CustomSelectValue",
 *     "custom_multiselect_value" = "CustomMultiSelectValue",
 *     "custom_file_value" = "CustomFileValue",
 *     "custom_date_value" = "CustomDateValue",
 *     "custom_datetime_value" = "CustomDateTimeValue"
 * })
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class CustomInputValue
{
    use Id;

    /**
     * Vlastní pole přihlášky.
     *
     * @ORM\ManyToOne(targetEntity="CustomInput", inversedBy="customInputValues", cascade={"persist"})
     */
    protected CustomInput $input;

    /**
     * Uživatel.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", inversedBy="customInputValues", cascade={"persist"})
     */
    protected User $user;

    public function __construct(CustomInput $input, User $user)
    {
        $this->input = $input;
        $this->user = $user;
    }

    public function getId(): int
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
