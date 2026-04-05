<?php

namespace App\Filament\Resources\Fields\Tables;

use App\Models\Entity;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity.name')->label('Entity')->badge()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('type')->badge(),
                IconColumn::make('is_required')->boolean()->label('Req.'),
                IconColumn::make('is_listed')->boolean()->label('Listed'),
                IconColumn::make('is_editable')->boolean()->label('Editable'),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('entity_id')
                    ->label('Entity')
                    ->options(Entity::pluck('name', 'id')),
                SelectFilter::make('type')
                    ->options([
                        'text' => 'Text', 'number' => 'Number',
                        'boolean' => 'Boolean', 'date' => 'Date',
                        'json' => 'JSON', 'media' => 'Media',
                    ]),
            ])
            ->defaultSort('sort_order');
    }
}