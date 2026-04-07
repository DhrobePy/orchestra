<?php

namespace App\Filament\Resources\Products\ProductVariantResource\RelationManagers;

use App\Models\Branch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    protected static ?string $title = 'Branch Prices';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->schema([
                    Select::make('branch_id')
                        ->label('Factory / Branch')
                        ->options(
                            Branch::orderBy('name')->pluck('name', 'id')->toArray()
                        )
                        ->nullable()
                        ->placeholder('All Branches / Default'),

                    Select::make('price_type')
                        ->label('Price Type')
                        ->options([
                            'retail'      => 'Retail Price',
                            'wholesale'   => 'Wholesale',
                            'distributor' => 'Distributor',
                            'special'     => 'Special Price',
                        ])
                        ->nullable()
                        ->placeholder('Standard'),
                ]),

            Grid::make(2)
                ->schema([
                    TextInput::make('price')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('৳')
                        ->label('Price (BDT)'),

                    DatePicker::make('effective_date')
                        ->label('Effective Date')
                        ->default(now()->toDateString())
                        ->nullable(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Branch / Factory')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->branch?->name ?? 'All Branches';
                    }),

                TextColumn::make('price_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'retail'      => 'info',
                        'wholesale'   => 'warning',
                        'distributor' => 'primary',
                        'special'     => 'success',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Standard'),

                TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('effective_date', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add Branch Price')
                    ->using(function (array $data, string $model) {
                        $variant = $this->getOwnerRecord();
                        $data['variant_id'] = $variant->id;
                        $data['product_id'] = $variant->product_id;

                        return $model::create($data);
                    }),
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
