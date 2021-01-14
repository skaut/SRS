<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use App\Model\User\User;

/**
 * Objekt příjemce e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Recipient
{
    /**
     * E-mail příjemce.
     */
    private string $email;

    /**
     * Jméno příjemce.
     */
    private ?string $name;

    public function __construct(string $email, ?string $name = null)
    {
        $this->email = $email;
        $this->name  = $name;
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
