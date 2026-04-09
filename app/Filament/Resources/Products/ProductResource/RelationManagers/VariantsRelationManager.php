<?php

namespace App\Filament\Resources\Products\ProductResource\RelationManagers;

use App\Models\Branch;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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
                ->columns(2)
                ->schema([
                    TextInput::make('weight_kg')
                        ->numeric()
                        ->nullable()
                        ->step(0.01)
                        ->label('Weight / Size (kg)')
                        ->placeholder('e.g. 50'),

                    TextInput::make('grade')
                        ->nullable()
                        ->label('Grade')
                        ->placeholder('e.g. A, B, 1')
                        ->maxLength(20),

                    Select::make('branch_id')
                        ->label('Factory / Branch')
                        ->options(Branch::orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->nullable(),

                    TextInput::make('sku')
                        ->nullable()
                        ->label('SKU')
                        ->maxLength(100),

                    Select::make('unit')
                        ->options(['kg' => 'kg', 'bag' => 'Bag', 'pcs' => 'Pcs'])
                        ->default('kg')
                        ->required(),

                    TextInput::make('price')
                        ->numeric()
                        ->nullable()
                        ->prefix('৳')
                        ->label('Active Price (৳)'),

                    DatePicker::make('effective_date')
                        ->label('Price Effective Date')
                        ->nullable(),

                    TextInput::make('stock')
                        ->numeric()
                        ->default(0)
                        ->label('Stock'),

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
                TextColumn::make('weight_kg')
                    ->label('Weight')
                    ->formatStateUsing(fn ($state) => $state ? (int)$state . ' kg' : '—')
                    ->sortable(),

                TextColumn::make('grade')
                    ->label('Grade')
                    ->formatStateUsing(fn ($state) => $state ? strtoupper($state) : '—'),

                TextColumn::make('branch.name')
                    ->label('Factory / Branch')
                    ->placeholder('—'),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Active Price')
                    ->formatStateUsing(fn ($state) => $state ? '৳ ' . number_format((float)$state, 2) : '—')
                    ->alignRight(),

                TextColumn::make('effective_date')
                    ->label('Effective')
                    ->date('d M Y'),

                TextColumn::make('name')
                    ->label('Display Name')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->defaultSort('weight_kg')
            ->headerActions([
                CreateAction::make()->label('Add Variant'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
