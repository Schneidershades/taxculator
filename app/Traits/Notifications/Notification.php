<?php

namespace App\Traits\Notifications;

use ReflectionClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification as BaseNotification;

class Notification extends BaseNotification
{
    public function models()
    {
        $reflection = new ReflectionClass($this);

        $params = $reflection->getConstructor()->getParameters();

        return array_map(function ($param) {
            $class = $param->getClass();

            if (!$class->isSubclassOf(Model::class)) {
                return;
            }

            return [
                'id' => $this->{$param->name}->id,
                'class' => $class->name
            ];
        }, $params);
    }
}
