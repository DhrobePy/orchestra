<?php

namespace App\Filament\Resources\Relationships\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RelationshipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity.name')->label('From')->badge()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('relatedEntity.name')->label('To')->badge(),
                TextColumn::make('foreign_key'),
                TextColumn::make('local_key'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'hasOne'        => 'Has One',
                        'hasMany'       => 'Has Many',
                        'belongsTo'     => 'Belongs To',
                        'belongsToMany' => 'Belongs To Many',
                    ]),
            ]);
    }
}