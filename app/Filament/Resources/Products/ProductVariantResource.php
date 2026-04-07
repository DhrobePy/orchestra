<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\ProductVariantResource\Pages\CreateProductVariant;
use App\Filament\Resources\Products\ProductVariantResource\Pages\EditProductVariant;
use App\Filament\Resources\Products\ProductVariantResource\Pages\ListProductVariants;
use App\Filament\Resources\Products\ProductVariantResource\Pages\ViewProductVariant;
use App\Filament\Resources\Products\ProductVariantResource\RelationManagers\PricesRelationManager;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Variant Details')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('product_id')
                                ->required()
                                ->label('Product')
                                ->options(
                                    Product::orderBy('name')->pluck('name', 'id')->toArray()
                                )
                                ->searchable(),

                            TextInput::make('name')
                                ->required()
                                ->label('Variant Name')
                                ->helperText('e.g. 50kg Grade A, 74kg Grade B'),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('sku')
                                ->nullable()
                                ->label('SKU'),

                            TextInput::make('weight_kg')
                                ->numeric()
                                ->nullable()
                                ->step(0.01)
                                ->label('Weight / Size (kg)'),

                            Select::make('unit')
                                ->options([
                                    'kg'  => 'kg',
                                    'bag' => 'Bag',
                                    'pcs' => 'Pcs',
                                ])
                                ->default('kg')
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('price')
                                ->numeric()
                                ->nullable()
                                ->prefix('৳')
                                ->label('Base Price (optional)'),

                            TextInput::make('stock')
                                ->numeric()
                                ->default(0)
                                ->label('Stock'),
                        ]),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Variant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->searchable(),

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
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProductVariants::route('/'),
            'create' => CreateProductVariant::route('/create'),
            'view'   => ViewProductVariant::route('/{record}'),
            'edit'   => EditProductVariant::route('/{record}/edit'),
        ];
    }
}
