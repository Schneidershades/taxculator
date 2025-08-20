<?php

namespace App\Traits\Api;

use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait DiscoverResources
{
    /**
     * Get a map of all API Resource and Collection classes
     * under app/Http/Resources by their short name.
     *
     * Cached indefinitely; clear cache on deploy when resources change.
     *
     * @return array<string,string>  [ 'UserResource' => 'App\Http\Resources\User\UserResource', … ]
     */
    protected function getResourceMapping(): array
    {
        return Cache::rememberForever('api_resource_mapping', function () {
            $mapping = [];
            $baseDir = app_path('Http/Resources');

            if (! is_dir($baseDir)) {
                return $mapping;
            }

            foreach (File::allFiles($baseDir) as $file) {
                $path = $file->getRealPath();

                // Build the FQCN: strip app_path(), swap separators, trim .php
                $relative = Str::after($path, app_path() . DIRECTORY_SEPARATOR);
                $class    = 'App\\' . str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    $relative
                );

                try {
                    $ref = new ReflectionClass($class);

                    // Only include instantiable JSON Resources
                    if (
                        $ref->isInstantiable() &&
                        (
                            $ref->isSubclassOf(JsonResource::class) ||
                            $ref->isSubclassOf(ResourceCollection::class)
                        )
                    ) {
                        $mapping[$ref->getShortName()] = $ref->getName();
                    }
                } catch (\ReflectionException $e) {
                    // Not a class, or invalid—skip
                }
            }

            return $mapping;
        });
    }

    /**
     * Resolve a resource class by short name (e.g. “UserResource”).
     * Falls back to JsonResource or ResourceCollection as needed.
     *
     * @param  string  $shortName
     * @param  bool    $isCollection
     * @return string
     */
    protected function resolveResourceClass(string $shortName, bool $isCollection = false): string
    {
        $map = $this->getResourceMapping();

        if ($isCollection) {
            // e.g. “UserCollection”
            $key = Str::finish($shortName, 'Collection');
            return $map[$key] ?? ResourceCollection::class;
        }

        // e.g. “UserResource”
        $key = Str::finish($shortName, 'Resource');
        return $map[$key] ?? JsonResource::class;
    }
}
