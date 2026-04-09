<?php

namespace App\Filament\Pages;

use App\Models\Entity;
use App\Models\Field;
use App\Services\DynamicMigrationService;
use App\Services\SchemaCache;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DBSchema;
use UnitEnum;

class ManageFieldOptions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static string|UnitEnum|null    $navigationGroup = 'Schema Builder';
    protected static ?string                 $navigationLabel = 'Field Options';
    protected static ?int                    $navigationSort  = 3;
    protected string $view = 'filament.pages.manage-field-options';

    // ── All form state lives here, no statePath ──────────────────────
    public ?int $selectedEntityId = null;
    public array $fields = [];

    public function mount(): void
    {
        $this->form->fill([
            'selectedEntityId' => null,
            'fields'           => [],
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Select::make('selectedEntityId')
                ->label('Select Entity')
                ->options(Entity::pluck('name', 'id'))
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->selectedEntityId = $state;
                    $this->loadFields($state);
                })
                ->required(),

            Repeater::make('fields')
                ->label('Fields')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('name')
                            ->label('Field Name')
                            ->required()
                            ->alphaDash()
                            ->maxLength(64),

                        TextInput::make('label')
                            ->label('Display Label')
                            ->required()
                            ->maxLength(128),

                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'text'     => 'Text',
                                'textarea' => 'Textarea',
                                'number'   => 'Number',
                                'integer'  => 'Integer',
                                'boolean'  => 'Boolean',
                                'date'     => 'Date',
                                'datetime' => 'Date & Time',
                                'select'   => 'Select (Dropdown)',
                                'json'     => 'JSON',
                                'media'    => 'Media / File',
                            ])
                            ->required(),
                    ]),

                    Grid::make(4)->schema([
                        Toggle::make('is_required')->label('Required')->default(false),
                        Toggle::make('is_listed')->label('Show in Table')->default(true),
                        Toggle::make('is_editable')->label('Editable')->default(true),
                        TextInput::make('sort_order')->label('Sort Order')->numeric()->default(0),
                    ]),

                    TextInput::make('validation_rules')
                        ->label('Validation Rules (comma separated)')
                        ->placeholder('min:1, max:255, email')
                        ->columnSpanFull(),
                ])
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn(array $state) => $state['label'] ?? 'New Field')
                ->addActionLabel('Add Field')
                ->visible(fn() => $this->selectedEntityId !== null),
        ]);
        // No ->statePath('data') — state maps directly to public properties
    }

    protected function loadFields(?int $entityId): void
    {
        if (!$entityId) {
            $this->fields = [];
            return;
        }

        $this->fields = Field::where('entity_id', $entityId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($f) => [
                'name'             => $f->name,
                'label'            => $f->label,
                'type'             => $f->type,
                'is_required'      => (bool) $f->is_required,
                'is_listed'        => (bool) $f->is_listed,
                'is_editable'      => (bool) $f->is_editable,
                'sort_order'       => $f->sort_order,
                'validation_rules' => $f->validation_rules
                    ? implode(', ', $f->validation_rules)
                    : '',
            ])
            ->toArray();
    }

    public function save(): void
    {
        if (!$this->selectedEntityId) {
            Notification::make()->title('Please select an entity first.')->warning()->send();
            return;
        }

        $entity        = Entity::findOrFail($this->selectedEntityId);
        $migration     = app(DynamicMigrationService::class);
        $cache         = app(SchemaCache::class);
        $existingNames = Field::where('entity_id', $entity->id)->pluck('name')->toArray();
        $incomingNames = [];

        // Create table if it doesn't exist
        if (!DBSchema::hasTable($entity->table_name)) {
            $migration->createTable($entity->table_name, $this->fields);

            foreach ($this->fields as $index => $fieldData) {
                $incomingNames[] = $fieldData['name'];
                $rules = array_filter(array_map('trim', explode(',', $fieldData['validation_rules'] ?? '')));
                Field::updateOrCreate(
                    ['entity_id' => $entity->id, 'name' => $fieldData['name']],
                    ['label'            => $fieldData['label'],
                     'type'             => $fieldData['type'],
                     'is_required'      => $fieldData['is_required'],
                     'is_listed'        => $fieldData['is_listed'],
                     'is_editable'      => $fieldData['is_editable'],
                     'sort_order'       => $index,
                     'validation_rules' => $rules ?: null]
                );
            }

            $cache->flushEntity($entity->id);
            Notification::make()->title("Table '{$entity->table_name}' created successfully.")->success()->send();
            return;
        }

        // Table exists — sync columns
        foreach ($this->fields as $index => $fieldData) {
            $incomingNames[] = $fieldData['name'];
            $rules = array_filter(array_map('trim', explode(',', $fieldData['validation_rules'] ?? '')));

            Field::updateOrCreate(
                ['entity_id' => $entity->id, 'name' => $fieldData['name']],
                ['label'            => $fieldData['label'],
                 'type'             => $fieldData['type'],
                 'is_required'      => $fieldData['is_required'],
                 'is_listed'        => $fieldData['is_listed'],
                 'is_editable'      => $fieldData['is_editable'],
                 'sort_order'       => $index,
                 'validation_rules' => $rules ?: null]
            );

            in_array($fieldData['name'], $existingNames)
                ? $migration->modifyField($entity->table_name, $fieldData)
                : $migration->addField($entity->table_name, $fieldData);
        }

        // Drop removed fields
        foreach (array_diff($existingNames, $incomingNames) as $dropped) {
            Field::where('entity_id', $entity->id)->where('name', $dropped)->delete();
            $migration->dropField($entity->table_name, $dropped);
        }

        $cache->flushEntity($entity->id);
        Notification::make()->title('Fields saved successfully.')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Fields')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
