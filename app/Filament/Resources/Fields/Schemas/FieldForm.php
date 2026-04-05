<?php

namespace App\Filament\Resources\Fields\Schemas;

use App\Models\Entity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('entity_id')
                ->label('Entity')
                ->options(Entity::pluck('name', 'id'))
                ->required()
                ->searchable(),

            TextInput::make('name')
                ->required()
                ->alphaDash()
                ->maxLength(64)
                ->helperText('Snake_case e.g. product_name'),

            TextInput::make('label')
                ->required()
                ->maxLength(128),

            Select::make('type')
                ->required()
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
                ->live(),

            TextInput::make('sort_order')
                ->numeric()
                ->default(0),

            TagsInput::make('validation_rules')
                ->placeholder('Add rule e.g. min:1')
                ->columnSpanFull(),

            Toggle::make('is_required')->default(false)->inline(),
            Toggle::make('is_listed')->default(true)->inline(),
            Toggle::make('is_editable')->default(true)->inline(),
        ]);
    }
}