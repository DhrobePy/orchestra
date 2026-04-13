<?php

declare(strict_types=1);

namespace App\Filament\Resources\DynamicRecords;

use App\Filament\Resources\DynamicRecords\Pages;
use App\Models\Entity;
use App\Models\Field;
use App\Services\RolePermissionService;
use BackedEnum;
use Filament\Actions\Action as RowAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DynamicRecordResource extends Resource
{
    protected static ?string $model = null;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;
    protected static bool $shouldRegisterNavigation = false;

    /**
     * Putting {table} in the slug means every URL carries the entity name:
     *   /admin/dynamic/{table}          → list
     *   /admin/dynamic/{table}/create   → create
     *   /admin/dynamic/{table}/{id}/edit → edit
     *
     * This matches the URLs AppServiceProvider already generates for nav items,
     * and allows request()->route('table') to reliably return the entity name
     * on every page load (including Livewire component boot).
     */
    protected static ?string $slug = 'dynamic/{table}';

    protected static ?Entity $currentEntity = null;
    protected static array $dynamicModels = [];

    /**
     * FK field names that cannot be resolved via stem-pluralization alone.
     * null = skip FK select, fall back to plain integer input.
     */
    private static array $fkTableOverrides = [
        'po_id'           => 'purchase_orders',
        'grn_id'          => 'goods_received_notes',
        'return_id'       => 'purchase_returns',
        'trip_id'         => 'trip_assignments',
        'from_account_id' => 'bank_accounts',
        'to_account_id'   => 'bank_accounts',
        'bank_account_id' => 'bank_accounts',
        'manager_id'      => 'employees',
        'parent_id'       => null, // self-referencing chart-of-accounts — skip
    ];

    /**
     * Context-aware FK overrides: [entity_table_name => [field_name => target_table]].
     * These take priority over global $fkTableOverrides for the specified entity.
     */
    private static array $contextFkOverrides = [
        'expense_subcategories' => [
            'category_id' => 'expense_categories',
        ],
        'expense_vouchers' => [
            'category_id'    => 'expense_categories',
            'subcategory_id' => 'expense_subcategories',
            'bank_account_id'=> 'bank_accounts',
            'approved_by'    => null, // plain integer
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Entity resolution
    // ─────────────────────────────────────────────────────────────────────────

    public static function getCurrentEntity(): ?Entity
    {
        if (static::$currentEntity !== null) {
            return static::$currentEntity;
        }

        $tableName = null;

        // With slug = 'dynamic/{table}', every direct page load has request()->route('table').
        if (request()->route('table')) {
            $tableName = request()->route('table');
        } else {
            // Livewire AJAX goes to /livewire-xxx/update — route('table') is null there.
            // The real page URL is preserved in the Referer header.
            $referer = request()->header('Referer', '');
            $path    = ltrim(parse_url($referer, PHP_URL_PATH) ?? '', '/');

            // Match admin/dynamic/{table}[/create | /{id}/edit | ...]
            if (preg_match('#^admin/dynamic/([^/]+)#', $path, $m)) {
                $candidate = $m[1];
                if (!in_array($candidate, ['create', 'edit'], true)) {
                    $tableName = $candidate;
                }
            }
        }

        if (!$tableName) {
            return null;
        }

        static::$currentEntity = Entity::where('table_name', $tableName)
            ->with(['fields' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        return static::$currentEntity;
    }

    /**
     * Filament internally calls getUrl() for breadcrumbs, navigation badges, etc.
     * without passing the {table} route parameter. This override injects it
     * automatically from the current request or active entity context.
     */
    public static function getUrl(
        ?string $name = null,
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?\Illuminate\Database\Eloquent\Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        if (!isset($parameters['table'])) {
            $parameters['table'] = request()->route('table')
                ?? static::getCurrentEntity()?->table_name
                ?? '';
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }

    public static function getModelLabel(): string
    {
        return static::getCurrentEntity()?->name ?? 'Record';
    }

    public static function getPluralModelLabel(): string
    {
        return static::getCurrentEntity()?->name ?? 'Records';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Model resolution (eval-based dynamic model)
    // ─────────────────────────────────────────────────────────────────────────

    public static function getModel(): string
    {
        $entity = static::getCurrentEntity();

        if (!$entity) {
            // Return a concrete (non-abstract) stub so Filament doesn't crash
            // with "Cannot instantiate abstract class" during Livewire boot
            // before the entity context is resolved.
            $stub = 'App\\Models\\DynamicModel__Stub';
            if (!class_exists($stub)) {
                eval('namespace App\\Models; class DynamicModel__Stub extends \\Illuminate\\Database\\Eloquent\\Model { protected $guarded = []; }');
            }
            return $stub;
        }

        $tableName = $entity->table_name;
        $className = 'DynamicModel_' . Str::studly($tableName);
        $fullClass  = "App\\Models\\{$className}";

        if (!isset(static::$dynamicModels[$tableName])) {
            if (!class_exists($fullClass)) {
                eval(<<<PHP
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class {$className} extends Model {
    protected \$table   = '{$tableName}';
    protected \$guarded = [];
    public \$timestamps = true;
}
PHP);
            }
            static::$dynamicModels[$tableName] = $fullClass;
        }

        return static::$dynamicModels[$tableName];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Form
    // ─────────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        $entity = static::getCurrentEntity();
        if (!$entity) {
            return $schema->components([]);
        }

        $user    = auth()->user();
        $rbac    = app(RolePermissionService::class);
        $hidden  = $user ? $rbac->getHiddenFields($user, $entity->id)   : [];
        $readonly = $user ? $rbac->getReadonlyFields($user, $entity->id) : [];

        $components = $entity->fields
            ->reject(fn (Field $f) => in_array($f->name, $hidden, true))
            ->map(function (Field $f) use ($readonly, $entity) {
                $component = static::makeField($f, $entity);
                // If field is in readonly list, force-disable regardless of is_editable
                if (in_array($f->name, $readonly, true)) {
                    $component = $component->disabled();
                }
                return $component;
            })
            ->toArray();

        return $schema->components([
            Grid::make(2)->schema($components),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Table
    // ─────────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $entity = static::getCurrentEntity();
        if (!$entity) {
            return $table->columns([]);
        }

        // ── RBAC checks ────────────────────────────────────────────────────
        $user      = auth()->user();
        $rbac      = app(RolePermissionService::class);
        $moduleId  = $entity->module_id;
        $entityId  = $entity->id;

        $canEdit   = !$user || $rbac->canDo($user, 'can_edit',   $moduleId, $entityId);
        $canDelete = !$user || $rbac->canDo($user, 'can_delete', $moduleId, $entityId);
        $canBulk   = !$user || $rbac->canDo($user, 'can_bulk_action', $moduleId, $entityId);

        // ── Columns ────────────────────────────────────────────────────────
        $hidden = $user ? $rbac->getHiddenFields($user, $entityId) : [];

        $columns = $entity->fields
            ->where('is_listed', true)
            ->reject(fn (Field $f) => in_array($f->name, $hidden, true))
            ->map(fn (Field $f) => static::makeTableColumn($f))
            ->toArray();

        $columns[] = TextColumn::make('created_at')
            ->label('Created')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        // ── Filters ────────────────────────────────────────────────────────
        $filters = $entity->fields
            ->whereIn('type', ['boolean', 'select'])
            ->map(fn (Field $f) => match ($f->type) {
                'boolean' => TernaryFilter::make($f->name)->label($f->label),
                'select'  => SelectFilter::make($f->name)
                    ->label($f->label)
                    ->options(static::normalizeOptions($f->options)),
                default   => null,
            })
            ->filter()
            ->values()
            ->toArray();

        // ── Row actions (conditionally shown) ──────────────────────────────
        $recordActions = [];

        // Print action for expense vouchers
        if ($entity->table_name === 'expense_vouchers') {
            $recordActions[] = RowAction::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn ($record) => route('print.expense-voucher', $record->getKey()))
                ->openUrlInNewTab();
        }

        if ($canEdit) {
            $recordActions[] = EditAction::make()
                ->url(fn ($record) => static::getUrl('edit', [
                    'table'  => static::getCurrentEntity()?->table_name ?? '',
                    'record' => $record->getKey(),
                ]));
        }
        if ($canDelete) {
            $recordActions[] = DeleteAction::make();
        }

        // ── Bulk actions ───────────────────────────────────────────────────
        $toolbarActions = [];
        if ($canBulk && $canDelete) {
            $toolbarActions[] = BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]);
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->defaultSort('created_at', 'desc')
            ->recordActions($recordActions)
            ->toolbarActions($toolbarActions);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Form field builder
    // ─────────────────────────────────────────────────────────────────────────

    protected static function makeField(Field $field, ?Entity $entity = null): mixed
    {
        $entityTable = $entity?->table_name ?? '';

        // ── Special: voucher_number — read-only, auto-generated on save ──────
        if ($field->name === 'voucher_number') {
            return TextInput::make('voucher_number')
                ->label($field->label)
                ->readOnly()
                ->dehydrated(false)
                ->placeholder('Auto-generated on save')
                ->helperText('Will be assigned automatically when the record is created.');
        }

        // ── Special: expense_vouchers — payment_method with live() ───────────
        if ($entityTable === 'expense_vouchers' && $field->name === 'payment_method') {
            return Select::make('payment_method')
                ->label($field->label)
                ->options(static::normalizeOptions($field->options))
                ->required($field->is_required)
                ->searchable()
                ->placeholder('Select payment method…')
                ->live();
        }

        // ── Special: expense_vouchers — bank_account_id conditionally shown ──
        if ($entityTable === 'expense_vouchers' && $field->name === 'bank_account_id') {
            return Select::make('bank_account_id')
                ->label($field->label)
                ->options(function () {
                    try {
                        return DB::table('bank_accounts')
                            ->where('is_active', true)
                            ->orderBy('bank_name')
                            ->get()
                            ->mapWithKeys(fn ($row) => [
                                $row->id => $row->bank_name . ' — ' . $row->account_name . ' (' . $row->account_number . ')',
                            ])
                            ->toArray();
                    } catch (\Throwable) {
                        return [];
                    }
                })
                ->searchable()
                ->placeholder('Select bank account…')
                ->hidden(fn (Get $get): bool => !in_array($get('payment_method'), ['bank_transfer', 'cheque'], true))
                ->nullable();
        }

        // ── Special: expense_vouchers — category_id with live() ─────────────
        if ($entityTable === 'expense_vouchers' && $field->name === 'category_id') {
            $select = static::makeFkSelect($field, $entityTable);
            if ($select) {
                return $select->live();
            }
        }

        // ── Special: expense_vouchers — subcategory_id cascades from category ─
        if ($entityTable === 'expense_vouchers' && $field->name === 'subcategory_id') {
            return Select::make('subcategory_id')
                ->label($field->label)
                ->options(function (Get $get) {
                    $catId = $get('category_id');
                    try {
                        $query = DB::table('expense_subcategories');
                        try { $query->whereNull('deleted_at'); } catch (\Throwable) {}
                        if ($catId) {
                            $query->where('category_id', $catId);
                        }
                        return $query->orderBy('name')->pluck('name', 'id')->toArray();
                    } catch (\Throwable) {
                        return [];
                    }
                })
                ->searchable()
                ->placeholder('Select subcategory…')
                ->nullable();
        }

        // ── FK field: integer ending in _id → relationship select ─────────────
        if ($field->type === 'integer' && str_ends_with($field->name, '_id')) {
            $fkSelect = static::makeFkSelect($field, $entityTable);
            if ($fkSelect !== null) {
                return $field->is_editable ? $fkSelect : $fkSelect->disabled();
            }
        }

        $component = match ($field->type) {
            'textarea' => Textarea::make($field->name)
                ->label($field->label)
                ->required($field->is_required)
                ->rows(4)
                ->columnSpanFull(),

            'number' => TextInput::make($field->name)
                ->label($field->label)
                ->numeric()
                ->required($field->is_required),

            'integer' => TextInput::make($field->name)
                ->label($field->label)
                ->integer()
                ->required($field->is_required),

            'boolean' => Toggle::make($field->name)
                ->label($field->label)
                ->default(false),

            'date' => DatePicker::make($field->name)
                ->label($field->label)
                ->required($field->is_required),

            'datetime' => DateTimePicker::make($field->name)
                ->label($field->label)
                ->required($field->is_required),

            'select' => Select::make($field->name)
                ->label($field->label)
                ->options(static::normalizeOptions($field->options))
                ->required($field->is_required)
                ->searchable()
                ->placeholder('Select…'),

            'json' => KeyValue::make($field->name)
                ->label($field->label)
                ->columnSpanFull(),

            'media' => FileUpload::make($field->name)
                ->label($field->label)
                ->disk('public')
                ->directory('dynamic/' . ($field->entity->table_name ?? 'records'))
                ->image()
                ->imageEditor()
                ->maxSize(10240)
                ->columnSpanFull(),

            default => TextInput::make($field->name)
                ->label($field->label)
                ->required($field->is_required),
        };

        if (!$field->is_editable) {
            $component = $component->disabled();
        }

        return $component;
    }

    /**
     * Build a preloaded Select for a FK field (integer ending in _id).
     * Uses ->options() so all items are visible without needing to type.
     * Returns null if the related table cannot be resolved or is explicitly excluded.
     */
    protected static function makeFkSelect(Field $field, string $entityTable = ''): ?Select
    {
        // Per-request cache of entity table map
        static $entityMap = null;
        if ($entityMap === null) {
            $entityMap = Entity::all()->keyBy('table_name');
        }

        // 1. Context-aware overrides (entity-specific FK mappings)
        if ($entityTable && isset(static::$contextFkOverrides[$entityTable][$field->name])) {
            $targetTable = static::$contextFkOverrides[$entityTable][$field->name];
            if ($targetTable === null) {
                return null; // explicitly excluded for this entity
            }
        }
        // 2. Global overrides
        elseif (array_key_exists($field->name, static::$fkTableOverrides)) {
            $targetTable = static::$fkTableOverrides[$field->name];
            if ($targetTable === null) {
                return null;
            }
        } else {
            // 3. Auto-resolve: strip _id and try plural candidates
            $stem       = Str::beforeLast($field->name, '_id');
            $candidates = [
                Str::plural($stem),
                $stem,
                $stem . 'es',
                Str::snake(Str::plural(Str::studly($stem))),
            ];

            $targetTable = null;
            foreach ($candidates as $candidate) {
                if ($entityMap->has($candidate)) {
                    $targetTable = $candidate;
                    break;
                }
            }

            // 4. Fuzzy prefix fallback
            if (!$targetTable) {
                $match       = Entity::where('table_name', 'like', "{$stem}%")->first();
                $targetTable = $match?->table_name;
            }

            if (!$targetTable) {
                return null;
            }
        }

        $relatedEntity = $entityMap->get($targetTable)
            ?? Entity::where('table_name', $targetTable)->first();

        $titleField = $relatedEntity?->title_field ?? 'id';

        // Special label format for bank_accounts
        if ($targetTable === 'bank_accounts') {
            return Select::make($field->name)
                ->label($field->label)
                ->required($field->is_required)
                ->searchable()
                ->options(function () {
                    try {
                        return DB::table('bank_accounts')
                            ->where('is_active', true)
                            ->orderBy('bank_name')
                            ->get()
                            ->mapWithKeys(fn ($row) => [
                                $row->id => $row->bank_name . ' — ' . $row->account_name . ' (' . $row->account_number . ')',
                            ])
                            ->toArray();
                    } catch (\Throwable) {
                        return [];
                    }
                })
                ->placeholder('Select bank account…');
        }

        // Standard FK select — preloaded options
        return Select::make($field->name)
            ->label($field->label)
            ->required($field->is_required)
            ->searchable()
            ->options(function () use ($targetTable, $titleField): array {
                try {
                    $query = DB::table($targetTable);
                    // Soft-delete awareness
                    try {
                        if (in_array('deleted_at', DB::getSchemaBuilder()->getColumnListing($targetTable))) {
                            $query->whereNull('deleted_at');
                        }
                    } catch (\Throwable) {}
                    return $query
                        ->orderBy($titleField)
                        ->limit(500)
                        ->pluck($titleField, 'id')
                        ->toArray();
                } catch (\Throwable) {
                    return [];
                }
            })
            ->placeholder('Select…');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Table column builder
    // ─────────────────────────────────────────────────────────────────────────

    protected static function makeTableColumn(Field $field): mixed
    {
        // FK columns: show the label from the related table instead of raw ID
        if ($field->type === 'integer' && str_ends_with($field->name, '_id')) {
            return TextColumn::make($field->name)
                ->label($field->label)
                ->sortable()
                ->formatStateUsing(fn ($state) => $state ?: '—');
        }

        return match ($field->type) {
            'boolean' => ToggleColumn::make($field->name)
                ->label($field->label),

            'date' => TextColumn::make($field->name)
                ->label($field->label)
                ->date('d M Y')
                ->sortable(),

            'datetime' => TextColumn::make($field->name)
                ->label($field->label)
                ->dateTime('d M Y H:i')
                ->sortable(),

            'number' => TextColumn::make($field->name)
                ->label($field->label)
                ->numeric(2)
                ->sortable(),

            'select' => TextColumn::make($field->name)
                ->label($field->label)
                ->badge()
                ->sortable(),

            'media' => ImageColumn::make($field->name)
                ->label($field->label),

            'textarea', 'json' => TextColumn::make($field->name)
                ->label($field->label)
                ->limit(60)
                ->searchable(),

            default => TextColumn::make($field->name)
                ->label($field->label)
                ->searchable()
                ->sortable()
                ->limit(50),
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Some older field records were seeded with double-encoded JSON.
     * The Eloquent 'array' cast decodes once, leaving a string.
     * This method always returns a proper PHP array regardless.
     */
    protected static function normalizeOptions(mixed $options): array
    {
        if (is_array($options)) {
            return $options;
        }
        if (is_string($options) && $options !== '') {
            $decoded = json_decode($options, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resource config
    // ─────────────────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDynamicRecords::route('/'),
            'create' => Pages\CreateDynamicRecord::route('/create'),
            'edit'   => Pages\EditDynamicRecord::route('/{record}/edit'),
        ];
    }
}
