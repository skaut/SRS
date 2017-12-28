<?php

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


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CustomInput
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param CustomInput $input
     */
    public function setInput($input)
    {
        $this->input = $input;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
