<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DynamicModelGenerator
{
    public function generateModel(Entity $entity): string
    {
        $className = Str::studly($entity->name);

        if (class_exists("App\\Models\\Dynamic\\{$className}")) {
            return "App\\Models\\Dynamic\\{$className}";
        }

        $fillable = $this->generateFillable($entity);
        $casts    = $this->generateCasts($entity);
        $relations = $this->generateRelationships($entity);

        $code = <<<PHP
        namespace App\Models\Dynamic;

        use Illuminate\Database\Eloquent\Model;
        use Illuminate\Database\Eloquent\SoftDeletes;

        class {$className} extends Model
        {
            use SoftDeletes;

            protected \$table = '{$entity->table_name}';

            protected \$fillable = {$fillable};

            protected \$casts = {$casts};

            {$relations}
        }
        PHP;

        eval($code);

        return "App\\Models\\Dynamic\\{$className}";
    }

    public function generateFillable(Entity $entity): string
    {
        $fields = $entity->fields->pluck('name')->toArray();
        $encoded = json_encode($fields);
        return str_replace(['"', ','], ["'", ", "], $encoded);
    }

    public function generateCasts(Entity $entity): string
    {
        $casts = [];

        foreach ($entity->fields as $field) {
            $casts[$field->name] = match ($field->type) {
                'number'  => 'decimal:2',
                'boolean' => 'boolean',
                'date'    => 'date',
                'json'    => 'array',
                default   => 'string',
            };
        }

        $encoded = json_encode($casts);
        return str_replace('"', "'", $encoded);
    }

    public function generateRelationships(Entity $entity): string
    {
        $methods = '';

        foreach ($entity->relationships as $rel) {
            $relatedClass = Str::studly($rel->relatedEntity->name);
            $methodName   = Str::camel($rel->name);

            $methods .= match ($rel->type) {
                'hasOne' => <<<PHP

                public function {$methodName}()
                {
                    return \$this->hasOne(\\App\\Models\\Dynamic\\{$relatedClass}::class, '{$rel->foreign_key}', '{$rel->local_key}');
                }

                PHP,
                'hasMany' => <<<PHP

                public function {$methodName}()
                {
                    return \$this->hasMany(\\App\\Models\\Dynamic\\{$relatedClass}::class, '{$rel->foreign_key}', '{$rel->local_key}');
                }

                PHP,
                'belongsTo' => <<<PHP

                public function {$methodName}()
                {
                    return \$this->belongsTo(\\App\\Models\\Dynamic\\{$relatedClass}::class, '{$rel->foreign_key}', '{$rel->local_key}');
                }

                PHP,
                'belongsToMany' => <<<PHP

                public function {$methodName}()
                {
                    return \$this->belongsToMany(\\App\\Models\\Dynamic\\{$relatedClass}::class, '{$rel->foreign_key}');
                }

                PHP,
                default => '',
            };
        }

        return $methods;
    }
}