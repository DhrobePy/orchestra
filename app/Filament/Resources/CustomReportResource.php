<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomReportResource\Pages;
use App\Models\CustomReport;
use App\Services\ReportQueryBuilderService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action as TableAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CustomReportResource extends Resource
{
    protected static ?string $model           = CustomReport::class;
    protected static string|\BackedEnum|null  $navigationIcon  = null;
    protected static string|UnitEnum|null     $navigationGroup = 'Reports';
    protected static ?string                  $navigationLabel = 'Report Builder';
    protected static ?int                     $navigationSort  = 1;

    public static function form(Schema $form): Schema
    {
        $sources = collect(ReportQueryBuilderService::sources())
            ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])
            ->toArray();

        return $form->schema([
            Section::make('Report Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g. Monthly Sales Summary'),

                    Select::make('data_source')
                        ->label('Data Source')
                        ->options($sources)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('columns', [])),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpanFull()
                        ->placeholder('Optional description of what this report shows…'),
                ]),

            Section::make('Columns')
                ->description('Choose which columns to include, in order.')
                ->schema([
                    Repeater::make('columns')
                        ->label('')
                        ->schema([
                            Select::make('field')
                                ->label('Column')
                                ->options(function ($get) use ($sources) {
                                    $src = $get('../../data_source');
                                    $all = ReportQueryBuilderService::sources();
                                    if (!$src || !isset($all[$src])) return [];
                                    return collect($all[$src]['columns'])
                                        ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])
                                        ->toArray();
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $src = $get('../../data_source');
                                    $all = ReportQueryBuilderService::sources();
                                    $col = $all[$src]['columns'][$state] ?? null;
                                    if ($col) {
                                        $set('label', $col['label']);
                                        $set('type',  $col['type']);
                                    }
                                }),

                            TextInput::make('label')
                                ->label('Header Label')
                                ->required()
                                ->maxLength(60),

                            Select::make('type')
                                ->label('Type')
                                ->options([
                                    'string'   => 'Text',
                                    'currency' => 'Currency',
                                    'number'   => 'Number',
                                    'date'     => 'Date',
                                    'boolean'  => 'Yes/No',
                                ])
                                ->default('string'),
                        ])
                        ->columns(3)
                        ->addActionLabel('Add Column')
                        ->reorderable()
                        ->defaultItems(0),
                ]),

            Section::make('Filters')
                ->description('Define which filter controls appear when running the report.')
                ->schema([
                    Repeater::make('filters')
                        ->label('')
                        ->schema([
                            Select::make('field')
                                ->label('Field to Filter')
                                ->options(function ($get) {
                                    $src = $get('../../data_source');
                                    $all = ReportQueryBuilderService::sources();
                                    if (!$src || !isset($all[$src])) return [];
                                    return collect($all[$src]['filters'] ?? [])
                                        ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])
                                        ->toArray();
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $src = $get('../../data_source');
                                    $all = ReportQueryBuilderService::sources();
                                    $f   = $all[$src]['filters'][$state] ?? null;
                                    if ($f) {
                                        $set('label', $f['label']);
                                        $set('type',  $f['type']);
                                    }
                                }),

                            TextInput::make('label')
                                ->label('Filter Label')
                                ->required()
                                ->maxLength(60),

                            Select::make('type')
                                ->label('Input Type')
                                ->options([
                                    'text'       => 'Text Search',
                                    'select'     => 'Dropdown',
                                    'date_range' => 'Date Range',
                                ])
                                ->default('text'),
                        ])
                        ->columns(3)
                        ->addActionLabel('Add Filter')
                        ->defaultItems(0),
                ]),

            Section::make('Sorting')
                ->columns(2)
                ->schema([
                    Select::make('sort_by')
                        ->label('Sort By')
                        ->options(function ($get) {
                            $src = $get('data_source');
                            $all = ReportQueryBuilderService::sources();
                            if (!$src || !isset($all[$src])) return [];
                            return collect($all[$src]['columns'])
                                ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])
                                ->toArray();
                        }),

                    Select::make('sort_dir')
                        ->label('Direction')
                        ->options(['asc' => 'Ascending', 'desc' => 'Descending'])
                        ->default('desc'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('data_source')
                    ->formatStateUsing(fn ($s) => ReportQueryBuilderService::sources()[$s]['label'] ?? $s)
                    ->badge()->color('info'),
                TextColumn::make('columns')
                    ->label('Columns')
                    ->formatStateUsing(fn ($s) => count($s ?? []) . ' columns'),
                TextColumn::make('creator.name')->label('Created By')->default('—'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->actions([
                TableAction::make('run')
                    ->label('Run Report')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->url(fn (CustomReport $r) => route('filament.admin.run-report', ['report' => $r->id]))
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([
                DeleteBulkAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomReports::route('/'),
            'create' => Pages\CreateCustomReport::route('/create'),
            'edit'   => Pages\EditCustomReport::route('/{record}/edit'),
        ];
    }
}
