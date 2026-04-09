<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class CompanySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static string|UnitEnum|null    $navigationGroup = 'Settings';
    protected static ?string                 $navigationLabel = 'Company Settings';
    protected static ?int                    $navigationSort  = 10;
    protected string                         $view            = 'filament.pages.company-settings';

    public array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::get();

        $this->form->fill([
            'company_name' => $settings->company_name,
            'tagline'      => $settings->tagline,
            'address'      => $settings->address,
            'city'         => $settings->city,
            'phone'        => $settings->phone,
            'email'        => $settings->email,
            'website'      => $settings->website,
            'tax_id'       => $settings->tax_id,
            'logo'         => $settings->logo,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Branding')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Company Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('company')
                            ->maxSize(5120)
                            ->columnSpanFull()
                            ->helperText('Shown on invoices and statements. Recommended: transparent PNG, max 500×200px.'),

                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('tagline')
                            ->label('Tagline / Sub-heading')
                            ->maxLength(255)
                            ->placeholder('e.g. Your Trusted Partner'),
                    ]),

                Section::make('Contact & Location')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(100),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('website')
                            ->label('Website')
                            ->maxLength(255)
                            ->placeholder('e.g. www.example.com'),

                        TextInput::make('tax_id')
                            ->label('Tax ID / VAT / TIN')
                            ->maxLength(100),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(100),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // FileUpload returns the path as a string when single file; normalize just in case
        if (is_array($data['logo'] ?? null)) {
            $data['logo'] = array_values($data['logo'])[0] ?? null;
        }

        CompanySetting::get()->update($data);

        Notification::make()
            ->title('Company settings saved.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
