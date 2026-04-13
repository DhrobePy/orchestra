<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use Crumbls\Layup\Models\Page as LayupPage;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class LandingPageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static string|UnitEnum|null    $navigationGroup = 'Settings';
    protected static ?string                 $navigationLabel = 'Landing Page';
    protected static ?int                    $navigationSort  = 15;
    protected string                         $view            = 'filament.pages.landing-page-settings';

    public array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::get();
        $config   = $settings->getLandingConfig();

        $this->form->fill([
            'hero_title'        => $config['hero_title'],
            'hero_subtitle'     => $config['hero_subtitle'],
            'hero_style'        => $config['hero_style'],
            'show_staff_login'  => $config['show_staff_login'],
            'show_admin_login'  => $config['show_admin_login'],
            'staff_login_label' => $config['staff_login_label'],
            'admin_login_label' => $config['admin_login_label'],
            'nav_items'         => $config['nav_items'],
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Hero Section')
                    ->columns(2)
                    ->schema([
                        TextInput::make('hero_title')
                            ->label('Hero Title')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('e.g. Manage Everything in One Place'),

                        TextInput::make('hero_subtitle')
                            ->label('Hero Subtitle')
                            ->maxLength(255)
                            ->placeholder('e.g. Your complete business solution'),

                        Select::make('hero_style')
                            ->label('Hero Style')
                            ->options([
                                'glassmorphic' => 'Glassmorphic (dark blur card)',
                                'minimal'      => 'Minimal (light clean)',
                                'gradient'     => 'Gradient (vibrant)',
                            ])
                            ->default('glassmorphic')
                            ->required(),
                    ]),

                Section::make('Login Buttons')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_staff_login')
                            ->label('Show Staff Login Button')
                            ->default(true)
                            ->live(),

                        TextInput::make('staff_login_label')
                            ->label('Staff Login Label')
                            ->default('Staff Login')
                            ->maxLength(50)
                            ->visible(fn ($get) => $get('show_staff_login')),

                        Toggle::make('show_admin_login')
                            ->label('Show Admin Login Button')
                            ->default(true)
                            ->live(),

                        TextInput::make('admin_login_label')
                            ->label('Admin Login Label')
                            ->default('Admin Login')
                            ->maxLength(50)
                            ->visible(fn ($get) => $get('show_admin_login')),
                    ]),

                Section::make('Navigation Menu')
                    ->description('Items shown in the landing page header.')
                    ->schema([
                        Repeater::make('nav_items')
                            ->label('Menu Items')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Label')
                                    ->required()
                                    ->maxLength(60)
                                    ->placeholder('e.g. About'),

                                TextInput::make('url')
                                    ->label('URL or Path')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('/pages/about')
                                    ->helperText('Use a path like /pages/about or a full URL.'),

                                Toggle::make('new_tab')
                                    ->label('Open in new tab')
                                    ->default(false),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Menu Item')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0),

                        Placeholder::make('layup_pages_hint')
                            ->label('Available Layup Pages')
                            ->content(function (): string {
                                try {
                                    $pages = LayupPage::whereNotNull('slug')
                                        ->orderBy('title')
                                        ->get(['title', 'slug']);

                                    if ($pages->isEmpty()) {
                                        return 'No Layup pages created yet. Create pages at Admin → Pages.';
                                    }

                                    return $pages->map(fn ($p) =>
                                        "• {$p->title}  →  /pages/{$p->slug}"
                                    )->join("\n");
                                } catch (\Throwable) {
                                    return 'Could not load Layup pages.';
                                }
                            }),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        CompanySetting::get()->update([
            'landing_page' => [
                'hero_title'        => $data['hero_title'],
                'hero_subtitle'     => $data['hero_subtitle'],
                'hero_style'        => $data['hero_style'],
                'show_staff_login'  => $data['show_staff_login'],
                'show_admin_login'  => $data['show_admin_login'],
                'staff_login_label' => $data['staff_login_label'] ?? 'Staff Login',
                'admin_login_label' => $data['admin_login_label'] ?? 'Admin Login',
                'nav_items'         => $data['nav_items'] ?? [],
            ],
        ]);

        Notification::make()
            ->title('Landing page settings saved.')
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

            Action::make('preview')
                ->label('Preview Landing Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url('/')
                ->openUrlInNewTab(),
        ];
    }
}
