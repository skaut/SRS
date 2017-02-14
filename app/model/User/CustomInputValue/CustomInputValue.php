<?php

namespace App\Model\User\CustomInputValue;

use App\Model\Settings\CustomInput\CustomInput;
use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * @ORM\Entity(repositoryClass="CustomInputValueRepository")
 * @ORM\Table(name="custom_input_value")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox_value" = "CustomCheckboxValue",
 *     "custom_text_value" = "CustomTextValue"
 * })
 */
abstract class CustomInputValue
{
    use Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\Settings\CustomInput\CustomInput", cascade={"persist"})
     * @var CustomInput
     */
    protected $input;

    /**
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