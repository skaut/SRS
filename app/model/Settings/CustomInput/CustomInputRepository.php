<?php

namespace App\Model\Settings\CustomInput;

use Kdyby\Doctrine\EntityRepository;

class CustomInputRepository extends EntityRepository
{
    public function createCheckBox($name) {
        $checkbox = new CustomCheckbox();
        $checkbox->setName($name);
        $checkbox->setPosition($this->countBy());
        $this->_em->persist($checkbox);
        $this->_em->flush();
    }

    public function createText($name) {
        $text = new CustomText();
        $text->setName($name);
        $text->setPosition($this->countBy());
        $this->_em->persist($text);
        $this->_em->flush();
    }
}