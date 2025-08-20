<?php

namespace App\Traits\Api;

use Illuminate\Support\Str;

trait HasIdentifier
{
    public static function bootHasIdentifier()
    {
        static::creating(function ($model) {
            $model->identifier = $model->generateIdentifier(
                $model->identifier ?? null
            );
        });
    }

    protected function generateIdentifier(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        // $prefix = strtoupper(class_basename($this));
        // $random = Str::upper(Str::random(6));

        // return now()->format('YmdHis')."-{$random}";
    }
}
