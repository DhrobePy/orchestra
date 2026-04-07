<?php

namespace App\Filament\Resources\Products\ProductResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Product Variants';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->label('Variant Name')
                                ->helperText('e.g. 50kg Grade A'),

                            TextInput::make('sku')
                                ->nullable()
                                ->label('Variant SKU'),

                            Select::make('unit')
                                ->options([
                                    'kg'  => 'kg',
                                    'bag' => 'Bag',
                                    'pcs' => 'Pcs',
                                ])
                                ->default('kg')
                                ->required(),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('weight_kg')
                                ->numeric()
                                ->nullable()
                                ->step(0.01)
                                ->label('Weight / Size (kg)'),

                            TextInput::make('price')
                                ->numeric()
                                ->nullable()
                                ->prefix('৳')
                                ->label('Base Price')
                                ->helperText('Optional — set per-branch in prices tab'),

                            TextInput::make('stock')
                                ->numeric()
                                ->default(0)
                                ->label('Stock Count'),
                        ]),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku'),

                TextColumn::make('weight_kg')
                    ->formatStateUsing(function ($state) {
                        if ($state === null || $state === '') {
                            return '—';
                        }

                        return number_format((float) $state, 2) . ' kg';
                    }),

                TextColumn::make('price')
                    ->formatStateUsing(function ($state) {
                        if ($state === null || $state === '') {
                            return '—';
                        }

                        return '৳ ' . number_format((float) $state, 2);
                    })
                    ->alignRight(),

                TextColumn::make('stock')
                    ->alignRight(),

                TextColumn::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make()->label('Add Variant'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
