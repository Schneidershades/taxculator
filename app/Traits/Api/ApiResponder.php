<?php

namespace App\Traits\Api;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Api\DiscoverResources;

trait ApiResponder
{
    use DiscoverResources;

    /**
     * Base success responder with JSON structure.
     */
    protected function respondSuccess(array $payload, int $code = 200)
    {
        return Response::json(array_merge(['success' => true], $payload), $code);
    }

    /**
     * Base error responder with JSON structure.
     */
    protected function respondError(string $message, int $code = 400)
    {
        return Response::json([
            'success' => false,
            'error'   => $message,
        ], $code);
    }

    /**
     * Display a list of models, filtered/sorted/paginated, wrapped in a ResourceCollection.
     */
    protected function showAll(Collection $collection, int $code = 200)
    {
        // 1) Figure out the humanized model name
        $first   = $collection->first();
        $short   = $first ? class_basename(get_class($first)) : 'Item';
        $label   = Str::of($short)->snake()->replace('_', ' ')->title();
        $message = "{$label} list retrieved successfully.";

        // 2) If empty, short‐circuit with an empty array
        if ($collection->isEmpty()) {
            return $this->respondSuccess([
                'message' => $message,
                'data'    => [],
            ], $code);
        }

        // 3) Pick the ResourceCollection class
        $transformerClass = $this->resolveResourceClass($short, true);

        // 4) Run your pipeline on the *raw* collection
        $collection = $this->filterData($collection,   $transformerClass);
        $collection = $this->sortData($collection,     $transformerClass);

        if ((int) request('per_page', 0) > 0) {
            $collection = $this->paginate($collection);
        }

        // 5) Transform *once* into your ResourceCollection
        /** @var ResourceCollection $resource */
        $resource = $this->transformData($collection, $transformerClass);

        // 6) Optionally cache the resource
        $resource = $this->cacheResponse($resource);

        // 7) Attach message & return the JSON response
        return $resource
            ->additional(['message' => $message, 'success' => true])
            ->response()
            ->setStatusCode($code);
    }

    /**
     * Display a single model instance wrapped in its JsonResource.
     * Auto-injects a creation or retrieval message based on HTTP code.
     */
    protected function showOne(Model $model, int $code = 200)
    {
        $short   = class_basename(get_class($model));
        $label   = Str::of($short)->snake()->replace('_', ' ')->title();
        $message = $code === 201
            ? "{$label} created successfully."
            : "{$label} retrieved successfully.";

        $transformerClass = $this->resolveResourceClass($short, false);
        $resource         = new $transformerClass($model);

        return $resource
            ->additional(['message' => $message, 'success' => true])
            ->response()
            ->setStatusCode($code);
    }

    /**
     * Shortcut for message-only responses (no data payload).
     */
    protected function showMessage($message, int $code = 200)
    {

        return $this->respondSuccess([
            'message' => $message,
            'success' => true,
        ], $code);
    }

    /**
     * Authentication-specific success response with token.
     */
    protected function authSuccess(Model $model, string $token, int $code = 200)
    {
        return $this->respondSuccess([
            'data' => $model,
            'meta' => ['token' => $token],
        ], $code);
    }

    /**
     * Authentication-specific error response.
     */
    protected function authError(string $message, int $code = 401)
    {
        return $this->respondError($message, $code);
    }

    protected function showDeleted(Model $model, int $code = 200)
    {
        $short = class_basename(get_class($model));
        $label = Str::of($short)
            ->snake()
            ->replace('_', ' ')     // “lead stage”
            ->title();

        $message = "{$label} deleted successfully.";

        return $this->respondSuccess([
            'message' => $message,
        ], $code);
    }

    protected function showUpdated(Model $model, int $code = 200)
    {
        $short = class_basename(get_class($model));
        $label = Str::of($short)->snake()->replace('_', ' ')->title();
        $message = "{$label} updated successfully.";

        return $this->respondSuccess([
            'message' => $message,
        ], $code);
    }

    /**
     * Filter collection by query parameters via transformer mapping.
     */
    protected function filterData(Collection $collection, string $transformerClass): Collection
    {
        foreach (request()->query() as $param => $value) {
            $attr = $transformerClass::originalAttribute($param);
            if ($attr !== null) {
                $collection = $collection->where($attr, $value);
            }
        }
        return $collection;
    }

    /**
     * Sort collection by query parameters via transformer mapping.
     */
    protected function sortData(Collection $collection, string $transformerClass): Collection
    {
        if ($asc = request('sort_by_asc')) {
            $attr = $transformerClass::originalAttribute($asc);
            if ($attr) {
                $collection = $collection->sortBy($attr);
            }
        }
        if ($desc = request('sort_by_desc')) {
            $attr = $transformerClass::originalAttribute($desc);
            if ($attr) {
                $collection = $collection->sortByDesc($attr);
            }
        }
        return $collection;
    }

    /**
     * Paginate a Collection into LengthAwarePaginator.
     */
    protected function paginate(Collection $collection): LengthAwarePaginator
    {
        $perPage = (int) request('per_page', 15);
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $slice   = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $collection->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    /**
     * Transform data via the given Resource or Collection.
     */
    protected function transformData($data, string $transformerClass)
    {
        return new $transformerClass($data);
    }

    /**
     * Cache the final response for 30 seconds based on full URL + sorted query.
     */
    protected function cacheResponse($data)
    {
        $query = request()->query();
        ksort($query);
        $key = request()->url() . '?' . http_build_query($query);

        return Cache::remember($key, 30, fn() => $data);
    }
}
