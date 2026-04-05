<?php

namespace App\Filament\Resources\Modules\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('icon'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('entities_count')
                    ->counts('entities')
                    ->label('Entities'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('name');
    }
}