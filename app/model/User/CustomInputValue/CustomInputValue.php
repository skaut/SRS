<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use App\Model\Settings\CustomInput\CustomInput;
use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * Abstraktní entita hodnota vlastního pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="CustomInputValueRepository")
 * @ORM\Table(name="custom_input_value")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox_value" = "CustomCheckboxValue",
 *     "custom_text_value" = "CustomTextValue",
 *     "custom_select_value" = "CustomSelectValue",
 *     "custom_file_value" = "CustomFileValue"
 * })
 */
abstract class CustomInputValue
{
    use Identifier;

    /**
     * Vlastní pole přihlášky.
     * @ORM\ManyToOne(targetEntity="\App\Model\Settings\CustomInput\CustomInput", inversedBy="customInputValues", cascade={"persist"})
     * @var CustomInput
     */
    protected $input;

    /**
     * Uživatel.
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", inversedBy="customInputValues", cascade={"persist"})
     * @var User
     */
    protected $user;


    public function getId() : int
    {
        return $this->id;
    }

    public function getInput() : CustomInput
    {
        return $this->input;
    }

    public function setInput(CustomInput $input) : void
    {
        $this->input = $input;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function setUser(User $user) : void
    {
        $this->user = $user;
    }
}
