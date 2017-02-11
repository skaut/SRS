<?php

namespace App\Model\User\CustomInputValue;


interface ICustomInputValue
{
    public function getValue();

    public function setValue($value);
}