<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\Products\ProductResource\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource\Pages\ViewProduct;
use App\Filament\Resources\Products\ProductResource\RelationManagers\VariantsRelationManager;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = Product::where('is_active', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Information')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('sku')
                                ->nullable()
                                ->label('Base SKU')
                                ->maxLength(100),
                        ]),

                    Grid::make(3)
                        ->schema([
                            Select::make('unit')
                                ->required()
                                ->options([
                                    'kg'    => 'kg',
                                    'bag'   => 'Bag',
                                    'pcs'   => 'Pcs',
                                    'litre' => 'Litre',
                                ])
                                ->default('kg'),

                            TextInput::make('category')
                                ->nullable(),

                            TextInput::make('price')
                                ->numeric()
                                ->nullable()
                                ->prefix('৳')
                                ->label('Base/Default Price'),
                        ]),

                    Textarea::make('description')
                        ->nullable()
                        ->rows(3),

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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->searchable(),

                TextColumn::make('category'),

                TextColumn::make('unit'),

                TextColumn::make('price')
                    ->formatStateUsing(function ($state) {
                        if ($state === null || $state === '') {
                            return '—';
                        }

                        return '৳ ' . number_format((float) $state, 2);
                    })
                    ->alignRight(),

                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->alignRight(),

                TextColumn::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
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
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view'   => ViewProduct::route('/{record}'),
            'edit'   => EditProduct::route('/{record}/edit'),
        ];
    }
}
