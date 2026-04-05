<?php

namespace App\Filament\Resources\Entities\Tables;

use App\Models\Module;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EntitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module.name')->label('Module')->badge()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('table_name')->searchable(),
                TextColumn::make('title_field'),
                TextColumn::make('fields_count')->counts('fields')->label('Fields'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(Module::pluck('name', 'id')),
            ])
            ->defaultSort('name');
    }
}