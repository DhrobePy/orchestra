<?php

namespace App\Filament\Resources\Relationships\Schemas;

use App\Models\Entity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RelationshipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('entity_id')
                ->label('From Entity')
                ->options(Entity::pluck('name', 'id'))
                ->required()
                ->searchable(),

            Select::make('related_entity_id')
                ->label('To Entity')
                ->options(Entity::pluck('name', 'id'))
                ->required()
                ->searchable(),

            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->helperText('Method name e.g. orderItems'),

            Select::make('type')
                ->required()
                ->options([
                    'hasOne'        => 'Has One',
                    'hasMany'       => 'Has Many',
                    'belongsTo'     => 'Belongs To',
                    'belongsToMany' => 'Belongs To Many',
                ]),

            TextInput::make('foreign_key')
                ->required()
                ->maxLength(255),

            TextInput::make('local_key')
                ->default('id')
                ->required()
                ->maxLength(255),
        ]);
    }
}