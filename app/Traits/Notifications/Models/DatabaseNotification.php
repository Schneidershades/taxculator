<?php

namespace App\Traits\Notifications\Models;

use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class DatabaseNotification extends BaseDatabaseNotification
{
    public function getModelsAttribute($value)
    {
        return (array) json_decode($value);
    }
}
