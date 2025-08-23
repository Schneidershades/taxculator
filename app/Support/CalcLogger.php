<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CalcLogger
{
    public static function contextId(): string
    {
        static $id;
        if (!$id) $id = request()->header('X-Request-Id') ?: Str::uuid()->toString();
        return $id;
    }

    public static function info(string $event, array $ctx = []): void
    {
        Log::channel('calc')->info($event, array_merge([
            'request_id' => self::contextId(),
            'path' => request()->path(),
            'ip' => request()->ip(),
        ], $ctx));
    }
}
