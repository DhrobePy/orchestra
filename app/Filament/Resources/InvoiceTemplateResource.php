<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceTemplateResource\Pages;
use App\Models\InvoiceTemplate;
use Filament\Forms\Components\ColorPicker;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class InvoiceTemplateResource extends Resource
{
    protected static ?string $model           = InvoiceTemplate::class;
    protected static string|\BackedEnum|null  $navigationIcon  = null;
    protected static string|UnitEnum|null     $navigationGroup = 'Settings';
    protected static ?string                  $navigationLabel = 'Invoice Templates';
    protected static ?int                     $navigationSort  = 20;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Template Identity')
                ->columns(3)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g. Standard A4 Invoice')
                        ->columnSpan(2),

                    Select::make('type')
                        ->label('Document Type')
                        ->options([
                            'credit_order'       => 'Sales Invoice',
                            'payment_receipt'    => 'Payment Receipt',
                            'customer_statement' => 'Customer Statement',
                        ])
                        ->required()
                        ->default('credit_order'),

                    Toggle::make('is_default')
                        ->label('Set as Default')
                        ->helperText('Automatically used when printing this document type.')
                        ->columnSpanFull(),
                ]),

            Section::make('Page Layout')
                ->columns(3)
                ->schema([
                    Select::make('config.paper_size')
                        ->label('Paper Size')
                        ->options(['A4' => 'A4', 'Letter' => 'US Letter', 'Legal' => 'Legal'])
                        ->default('A4')
                        ->required(),

                    Select::make('config.orientation')
                        ->label('Orientation')
                        ->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])
                        ->default('portrait')
                        ->required(),

                    Select::make('config.font_family')
                        ->label('Font Family')
                        ->options([
                            'Arial, Helvetica, sans-serif'     => 'Arial',
                            'Georgia, "Times New Roman", serif' => 'Georgia',
                            '"Trebuchet MS", sans-serif'        => 'Trebuchet MS',
                            '"Courier New", monospace'          => 'Courier New',
                        ])
                        ->default('Arial, Helvetica, sans-serif'),
                ]),

            Section::make('Colours')
                ->columns(3)
                ->schema([
                    ColorPicker::make('config.primary_color')
                        ->label('Primary (heading/accent)')
                        ->default('#1e3a5f'),

                    ColorPicker::make('config.accent_color')
                        ->label('Accent (highlights)')
                        ->default('#f59e0b'),

                    ColorPicker::make('config.text_color')
                        ->label('Body Text')
                        ->default('#111827'),

                    ColorPicker::make('config.border_color')
                        ->label('Border / Dividers')
                        ->default('#e5e7eb'),

                    ColorPicker::make('config.header_bg')
                        ->label('Header Background')
                        ->default('#1e3a5f'),

                    ColorPicker::make('config.header_text_color')
                        ->label('Header Text')
                        ->default('#ffffff'),
                ]),

            Section::make('Branding')
                ->columns(3)
                ->schema([
                    Select::make('config.logo_position')
                        ->label('Logo Position')
                        ->options([
                            'left'   => 'Left',
                            'center' => 'Center',
                            'right'  => 'Right',
                            'hidden' => 'Hide Logo',
                        ])
                        ->default('left'),

                    Toggle::make('config.show_company_address')
                        ->label('Show Company Address')
                        ->default(true),

                    Toggle::make('config.show_company_contact')
                        ->label('Show Phone & Email')
                        ->default(true),
                ]),

            Section::make('Header & Footer Text')
                ->columns(2)
                ->schema([
                    Textarea::make('config.header_text')
                        ->label('Custom Header Text')
                        ->rows(2)
                        ->placeholder('Optional text below company name…'),

                    Textarea::make('config.footer_text')
                        ->label('Footer Text')
                        ->rows(2)
                        ->default('Thank you for your business!'),

                    TextInput::make('config.watermark_text')
                        ->label('Watermark Text')
                        ->placeholder('e.g. PAID or CONFIDENTIAL — leave blank to disable')
                        ->maxLength(30),

                    Toggle::make('config.show_page_numbers')
                        ->label('Show Page Numbers'),
                ]),

            Section::make('Line-Item Columns')
                ->columns(4)
                ->description('Choose which columns appear in the items table.')
                ->schema([
                    Toggle::make('config.show_item_code')
                        ->label('Item Code')
                        ->default(false),

                    Toggle::make('config.show_description')
                        ->label('Description')
                        ->default(true),

                    Toggle::make('config.show_discount_column')
                        ->label('Discount Column')
                        ->default(true),

                    Toggle::make('config.show_tax_column')
                        ->label('Tax Column')
                        ->default(false),
                ]),

            Section::make('Summary & Totals')
                ->columns(3)
                ->schema([
                    Toggle::make('config.show_subtotal')
                        ->label('Subtotal Row')
                        ->default(true),

                    Toggle::make('config.show_discount_total')
                        ->label('Total Discount Row')
                        ->default(true),

                    Toggle::make('config.show_tax_total')
                        ->label('Total Tax Row')
                        ->default(false),
                ]),

            Section::make('Info Sections')
                ->columns(2)
                ->schema([
                    Toggle::make('config.show_payment_terms')
                        ->label('Payment Terms')
                        ->default(true),

                    Toggle::make('config.show_bank_details')
                        ->label('Bank Details')
                        ->default(true),

                    Toggle::make('config.show_notes')
                        ->label('Order Notes')
                        ->default(true),

                    Toggle::make('config.show_terms')
                        ->label('Terms & Conditions')
                        ->default(false)
                        ->live(),

                    Textarea::make('config.terms_text')
                        ->label('Terms & Conditions Text')
                        ->rows(4)
                        ->visible(fn ($get) => $get('config.show_terms'))
                        ->columnSpanFull(),
                ]),

            Section::make('Custom Header Fields')
                ->description('Extra label/value rows shown in the invoice header block.')
                ->schema([
                    Repeater::make('config.custom_header_fields')
                        ->label('')
                        ->schema([
                            TextInput::make('label')
                                ->required()
                                ->placeholder('e.g. Project #'),
                            TextInput::make('value')
                                ->placeholder('e.g. PRJ-2026-001'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add Field')
                        ->defaultItems(0)
                        ->reorderable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'credit_order'       => 'Sales Invoice',
                        'payment_receipt'    => 'Receipt',
                        'customer_statement' => 'Statement',
                        default              => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'credit_order'       => 'info',
                        'payment_receipt'    => 'success',
                        'customer_statement' => 'warning',
                        default              => 'gray',
                    }),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->actions([
                TableAction::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (InvoiceTemplate $r) => route('invoice.template.preview', $r))
                    ->openUrlInNewTab(),

                TableAction::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function (InvoiceTemplate $record) {
                        $clone = $record->replicate();
                        $clone->name       = 'Copy of ' . $record->name;
                        $clone->is_default = false;
                        $clone->save();
                        \Filament\Notifications\Notification::make()
                            ->title('Template duplicated — you can now edit your copy.')
                            ->success()->send();
                    }),

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
            'index'  => Pages\ListInvoiceTemplates::route('/'),
            'create' => Pages\CreateInvoiceTemplate::route('/create'),
            'edit'   => Pages\EditInvoiceTemplate::route('/{record}/edit'),
        ];
    }
}
