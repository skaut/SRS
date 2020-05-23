<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;
use function count;
use function explode;

/**
 * Entita vlastní výběrové pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_select")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomSelect extends CustomInput
{
    protected string $type = CustomInput::SELECT;

    /**
     * Možnosti výběrového pole oddělené čárkou.
     *
     * @ORM\Column(type="simple_array")
     */
    protected array $options = [];

    public function getOptions() : array
    {
        return $this->options;
    }

    public function setOptions(array $options) : void
    {
        $this->options = $options;
    }

    /**
     * Vrátí možnosti jako možnosti pro select.
     *
     * @return string[]
     */
    public function getSelectOptions() : array
    {
        $options = [];

        if (! $this->isMandatory()) {
            $options[0] = '';
        }

        for ($i = 0; $i < count($this->options); $i++) {
            $options[$i+1] = $this->options[$i];
        }

        return $options;
    }

    public function getOptionsText() : string
    {
        return implode(', ', $this->options);
    }
}
