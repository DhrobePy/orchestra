<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DynamicMigrationService
{
    public function createTable(string $tableName, array $fields = []): void
{
    if (Schema::hasTable($tableName)) {
        $this->syncColumns($tableName, $fields);
        return;
    }

    // Deduplicate fields by name — keep last occurrence
    $unique = [];
    foreach ($fields as $field) {
        $unique[$field['name']] = $field;
    }
    $fields = array_values($unique);

    Schema::create($tableName, function (Blueprint $table) use ($fields) {
        $table->id();
        foreach ($fields as $field) {
            $this->addColumn($table, $field);
        }
        $table->softDeletes();
        $table->timestamps();
    });
}

    public function syncColumns(string $tableName, array $fields): void
    {
        foreach ($fields as $field) {
            if (!Schema::hasColumn($tableName, $field['name'])) {
                $this->addField($tableName, $field);
            }
            // Don't modify existing columns during sync — too risky
        }
    }

    public function addField(string $tableName, array $field): void
    {
        if (Schema::hasColumn($tableName, $field['name'])) {
            return; // Already exists — skip silently
        }

        Schema::table($tableName, function (Blueprint $table) use ($field) {
            $this->addColumn($table, $field);
        });
    }

    public function modifyField(string $tableName, array $field): void
    {
        // If column doesn't exist, add it instead of modifying
        if (!Schema::hasColumn($tableName, $field['name'])) {
            $this->addField($tableName, $field);
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($field) {
            $col = $this->addColumn($table, $field);
            $col->change();
        });
    }

    public function dropField(string $tableName, string $fieldName): void
    {
        if (!Schema::hasColumn($tableName, $fieldName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($fieldName) {
            $table->dropColumn($fieldName);
        });
    }

    public function dropTable(string $tableName): void
    {
        Schema::dropIfExists($tableName);
    }

    private function addColumn(Blueprint $table, array $field): \Illuminate\Database\Schema\ColumnDefinition
    {
        $col = match ($field['type']) {
            'text'     => $table->string($field['name']),
            'textarea' => $table->text($field['name']),
            'number'   => $table->decimal($field['name'], 15, 2),
            'integer'  => $table->integer($field['name']),
            'boolean'  => $table->boolean($field['name'])->default(false),
            'date'     => $table->date($field['name']),
            'datetime' => $table->dateTime($field['name']),
            'select'   => $table->string($field['name']),
            'json'     => $table->json($field['name']),
            'media'    => $table->string($field['name'])->nullable(),
            default    => $table->string($field['name']),
        };

        if (empty($field['is_required'])) {
            $col->nullable();
        }

        return $col;
    }
}
