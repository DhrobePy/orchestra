<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Support\Facades\Cache;

class SchemaCache
{
    const TTL = 3600;

    public function getEntity(int $entityId): ?Entity
    {
        return Cache::remember("entity:{$entityId}", self::TTL, fn() =>
            Entity::with(['fields', 'relationships.relatedEntity'])->find($entityId)
        );
    }

    public function getEntityByTable(string $tableName): ?Entity
    {
        return Cache::remember("entity:table:{$tableName}", self::TTL, fn() =>
            Entity::with(['fields', 'relationships.relatedEntity'])
                ->where('table_name', $tableName)
                ->first()
        );
    }

    public function getFields(int $entityId): \Illuminate\Support\Collection
    {
        return Cache::remember("fields:entity:{$entityId}", self::TTL, fn() =>
            Entity::findOrFail($entityId)->fields()->orderBy('sort_order')->get()
        );
    }

    public function getAllEntities(): \Illuminate\Support\Collection
    {
        return Cache::remember('entities:all', self::TTL, fn() =>
            Entity::with(['fields', 'module'])->get()
        );
    }

    public function flushEntity(int $entityId): void
    {
        Cache::forget("entity:{$entityId}");
        Cache::forget("fields:entity:{$entityId}");
        Cache::forget('entities:all');
        // Also clear table-based keys for all entities
        Entity::all()->each(fn($e) =>
            Cache::forget("entity:table:{$e->table_name}")
        );
    }

    public function flushAll(): void
    {
        Cache::flush();
    }
}
