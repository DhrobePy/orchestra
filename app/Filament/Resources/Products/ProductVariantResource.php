<?php

namespace App\Filament\Resources\Products;

use App\Filament\Concerns\ChecksStaffPanel;
use App\Filament\Resources\Products\ProductVariantResource\Pages\CreateProductVariant;
use App\Filament\Resources\Products\ProductVariantResource\Pages\EditProductVariant;
use App\Filament\Resources\Products\ProductVariantResource\Pages\ListProductVariants;
use App\Filament\Resources\Products\ProductVariantResource\Pages\ViewProductVariant;
use App\Filament\Resources\Products\ProductVariantResource\RelationManagers\PricesRelationManager;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
    use ChecksStaffPanel;

    protected static ?string $model = ProductVariant::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Variant Identity')
                ->columns(2)
                ->schema([
                    Select::make('product_id')
                        ->required()
                        ->label('Product')
                        ->options(Product::orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->columnSpanFull(),

                    TextInput::make('sku')
                        ->nullable()
                        ->label('SKU')
                        ->maxLength(100),

                    Select::make('unit')
                        ->options(['kg' => 'kg', 'bag' => 'Bag', 'pcs' => 'Pcs'])
                        ->default('kg')
                        ->required(),

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

                    TextInput::make('name')
                        ->label('Display Name (auto-generated)')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->helperText('Auto-built from product + weight + grade + branch when saved.'),
                ]),

            Section::make('Pricing & Stock')
                ->columns(3)
                ->schema([
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

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
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('price')
                    ->label('Active Price')
                    ->formatStateUsing(fn ($state) => $state ? '৳ ' . number_format((float)$state, 2) : '—')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->label('Effective')
                    ->date('d M Y')
                    ->toggleable(),

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

                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
            ])
            ->defaultSort('product_id')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
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
