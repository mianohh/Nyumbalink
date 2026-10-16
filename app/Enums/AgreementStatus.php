<?php

namespace App\Enums;

enum AgreementStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Terminated => 'Terminated',
        };
    }
}
