<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use App\Model\User\User;

class Recipient
{
    private string $email;

    private ?string $name;

    public function __construct(string $email, ?string $name = null)
    {
        $this->email = $email;
        $this->name  = $name;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public static function createFromUser(User $user) : Recipient
    {
        return new Recipient($user->getEmail(), $user->getDisplayName());
    }
}
