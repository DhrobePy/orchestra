<?php

namespace App\Filament\Resources\Entities\Schemas;

use App\Models\Module;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EntityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('module_id')
                ->label('Module')
                ->options(Module::where('is_active', true)->pluck('name', 'id'))
                ->required()
                ->searchable(),

            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('table_name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Snake_case e.g. product_variants'),

            TextInput::make('title_field')
                ->default('name')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            KeyValue::make('options')
                ->columnSpanFull(),
        ]);
    }
}