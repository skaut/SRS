<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;
use function count;
use function explode;

/**
 * Entita vlastní výběrové pole s více možnostmi přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_multiselect")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomMultiSelect extends CustomInput
{
    protected string $type = CustomInput::MULTISELECT;

    /**
     * Možnosti výběrového pole oddělené čárkou.
     *
     * @ORM\Column(type="string")
     */
    protected string $options;

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
     *
     * @return string[]
     */
    public function getSelectOptions() : array
    {
        $options = [];

        $optionaArray = explode(', ', $this->options);
        for ($i = 0; $i < count($optionaArray); $i++) {
            $options[$i+1] = $optionaArray[$i];
        }

        return $options;
    }
}
