<?php

namespace App\Filament\Resources\Modules\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('icon')
                ->placeholder('heroicon-o-cube')
                ->maxLength(255),

            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->default(true),
        ]);
    }
}