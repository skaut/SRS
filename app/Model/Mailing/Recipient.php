<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use App\Model\User\User;

/**
 * Objekt příjemce e-mailu.
 */
class Recipient
{
    /**
     * @param string  $email E-mail příjemce.
     * @param ?string $name  Jméno příjemce.
     */
    public function __construct(
        private readonly string $email,
        private readonly string|null $name = null,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function isValid(): bool
    {
        return ! empty($this->email);
    }

    /**
     * Vytvoří objekt na základě údajů uživatele.
     */
    public static function createFromUser(User $user): Recipient|null
    {
        return new Recipient($user->getEmail(), $user->getDisplayName());
    }
}
