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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
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
                    FileUpload::make('image')
                        ->label('Product Image')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('products')
                        ->maxSize(4096)
                        ->columnSpanFull()
                        ->nullable(),

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->schema([
                    ImageEntry::make('image')
                        ->label('')
                        ->disk('public')
                        ->size(120)
                        ->defaultImageUrl(fn () => null)
                        ->columnSpan(1),

                    Section::make()
                        ->columnSpan(3)
                        ->schema([
                            TextEntry::make('name')
                                ->label('Product Name')
                                ->size(\Filament\Support\Enums\TextSize::Large)
                                ->weight(\Filament\Support\Enums\FontWeight::Bold),

                            Grid::make(3)->schema([
                                TextEntry::make('sku')->label('SKU')->placeholder('—'),
                                TextEntry::make('category')->label('Category')->placeholder('—'),
                                TextEntry::make('unit')->label('Unit'),
                            ]),

                            Grid::make(2)->schema([
                                TextEntry::make('price')
                                    ->label('Base Price')
                                    ->formatStateUsing(fn ($state) => $state ? '৳ ' . number_format((float) $state, 2) : '—'),

                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
                            ]),
                        ])
                        ->extraAttributes(['style' => 'background:transparent;border:none;box-shadow:none;padding:0;']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->size(40)
                    ->defaultImageUrl(null),

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
