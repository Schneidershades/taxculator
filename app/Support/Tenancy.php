<?php

namespace App\Support;

use App\Models\Tenant;

class Tenancy
{
    private static ?Tenant $current = null;

    public static function setCurrent(?Tenant $tenant): void
    {
        self::$current = $tenant;
    }

    public static function current(): ?Tenant
    {
        return self::$current;
    }
}

