<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

use function implode;

/**
 * Entita vlastní výběrové pole s více možnostmi přihlášky
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_multiselect')]
class CustomMultiSelect extends CustomInput
{
    protected string $type = CustomInput::MULTISELECT;

    /**
     * Možnosti výběrového pole oddělené čárkou
     *
     * @var string[]
     */
    #[ORM\Column(type: 'simple_array')]
    protected array $options = [];

    /**
     * @return string[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Vrátí možnosti jako možnosti pro select
     *
     * @return string[]
     */
    public function getSelectOptions(): array
    {
        return $this->options;
    }

    public function getOptionsText(): string
    {
        return implode(', ', $this->options);
    }
}
