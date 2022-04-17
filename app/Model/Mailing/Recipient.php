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
     * @param string      $email E-mail příjemce
     * @param string|null $name  Jméno příjemce
     */
    public function __construct(
        private string $email,
        private ?string $name = null
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Vytvoří objekt na základě údajů uživatele.
     */
    public static function createFromUser(User $user): Recipient
    {
        return new Recipient($user->getEmail(), $user->getDisplayName());
    }
}
