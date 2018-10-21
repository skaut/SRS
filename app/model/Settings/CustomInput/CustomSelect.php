<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;
use function count;
use function explode;

/**
 * Entita vlastní výběrové pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_select")
 */
class CustomSelect extends CustomInput
{
    /** @var string */
    protected $type = CustomInput::SELECT;

    /**
     * Možnosti výběrového pole oddělené čárkou.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $options;


    public function getOptions() : string
    {
        return $this->options;
    }

    public function setOptions(string $options) : void
    {
        $this->options = $options;
    }

    /**
     * Vrátí možnosti jako možnosti pro select.
     * @return string[]
     */
    public function getSelectOptions() : array
    {
        $options = [];

        if (! $this->isMandatory()) {
            $options[0] = '';
        }

        $optionaArray = explode(', ', $this->options);
        for ($i = 0; $i < count($optionaArray); $i++) {
            $options[$i+1] = $optionaArray[$i];
        }

        return $options;
    }
}
