<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SchemaCache
{
    const TTL = 3600;

    /**
     * Request-level in-memory store.
     * Holds actual Eloquent objects so they are never serialized within a request.
     * Cleared automatically at end of each HTTP request (static properties reset per process).
     */
    private static array $memory = [];

    // ── Entity by ID ──────────────────────────────────────────────────────────

    public function getEntity(int $entityId): ?Entity
    {
        $key = "entity:{$entityId}";

        if (array_key_exists($key, self::$memory)) {
            return self::$memory[$key];
        }

        // Persistent cache stores the raw attribute array to avoid serializing Eloquent objects.
        $attrs = Cache::remember("{$key}:attrs", self::TTL, fn() =>
            Entity::with(['fields' => fn($q) => $q->orderBy('sort_order'), 'relationships.relatedEntity'])
                ->find($entityId)
                ?->toArray()   // plain PHP array — safe to serialize
        );

        $entity = $attrs ? $this->hydrateEntity($attrs) : null;

        return self::$memory[$key] = $entity;
    }

    // ── Entity by table name ──────────────────────────────────────────────────

    public function getEntityByTable(string $tableName): ?Entity
    {
        $key = "entity:table:{$tableName}";

        if (array_key_exists($key, self::$memory)) {
            return self::$memory[$key];
        }

        $attrs = Cache::remember("{$key}:attrs", self::TTL, fn() =>
            Entity::with(['fields' => fn($q) => $q->orderBy('sort_order'), 'relationships.relatedEntity'])
                ->where('table_name', $tableName)
                ->first()
                ?->toArray()
        );

        $entity = $attrs ? $this->hydrateEntity($attrs) : null;

        return self::$memory[$key] = $entity;
    }

    // ── Fields collection ─────────────────────────────────────────────────────

    public function getFields(int $entityId): Collection
    {
        $key = "fields:entity:{$entityId}";

        if (array_key_exists($key, self::$memory)) {
            return self::$memory[$key];
        }

        // Cache raw field attribute arrays
        $rows = Cache::remember("{$key}:attrs", self::TTL, fn() =>
            Entity::findOrFail($entityId)
                ->fields()
                ->orderBy('sort_order')
                ->get()
                ->toArray()    // plain array — safe to serialize
        );

        $fields = \App\Models\Field::hydrate($rows);

        return self::$memory[$key] = $fields;
    }

    // ── All entities ──────────────────────────────────────────────────────────

    public function getAllEntities(): Collection
    {
        $key = 'entities:all';

        if (array_key_exists($key, self::$memory)) {
            return self::$memory[$key];
        }

        $rows = Cache::remember("{$key}:attrs", self::TTL, fn() =>
            Entity::with(['fields', 'module'])->get()->toArray()
        );

        $entities = collect($rows)->map(fn($attrs) => $this->hydrateEntity($attrs));

        return self::$memory[$key] = $entities;
    }

    // ── Cache invalidation ────────────────────────────────────────────────────

    public function flushEntity(int $entityId): void
    {
        // Clear persistent cache
        Cache::forget("entity:{$entityId}:attrs");
        Cache::forget("fields:entity:{$entityId}:attrs");
        Cache::forget('entities:all:attrs');

        // Also clear table-name keyed caches
        Entity::all()->each(fn($e) =>
            Cache::forget("entity:table:{$e->table_name}:attrs")
        );

        // Clear in-memory store so this request sees fresh data too
        self::$memory = [];
    }

    public function flushAll(): void
    {
        Cache::flush();
        self::$memory = [];
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Reconstruct an Entity Eloquent model (with its loaded relations) from a
     * plain toArray() snapshot. Relations stored in the array snapshot are
     * re-hydrated as Eloquent model instances so downstream code can use
     * standard Eloquent accessors and relation helpers.
     */
    private function hydrateEntity(array $attrs): Entity
    {
        // Separate relation keys from column attributes
        $fields        = $attrs['fields']        ?? [];
        $relationships = $attrs['relationships'] ?? [];
        $module        = $attrs['module']        ?? null;

        unset($attrs['fields'], $attrs['relationships'], $attrs['module']);

        /** @var Entity $entity */
        $entity = (new Entity)->newFromBuilder($attrs);

        // Re-hydrate the 'fields' relation
        if (!empty($fields)) {
            $fieldModels = \App\Models\Field::hydrate($fields);
            $entity->setRelation('fields', $fieldModels);
        }

        // Re-hydrate a nested module relation if present
        if ($module) {
            $moduleModel = (new \App\Models\Module)->newFromBuilder($module);
            $entity->setRelation('module', $moduleModel);
        }

        // Re-hydrate 'relationships' relation (EntityRelationship + relatedEntity)
        if (!empty($relationships)) {
            $relModels = collect($relationships)->map(function ($relAttrs) {
                $relatedEntity = $relAttrs['related_entity'] ?? null;
                unset($relAttrs['related_entity']);

                $rel = (new \App\Models\Relationship)->newFromBuilder($relAttrs);

                if ($relatedEntity) {
                    $rel->setRelation('relatedEntity',
                        (new Entity)->newFromBuilder($relatedEntity)
                    );
                }

                return $rel;
            });
            $entity->setRelation('relationships', $relModels);
        }

        return $entity;
    }
}
