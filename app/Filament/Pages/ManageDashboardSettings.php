<?php

namespace App\Filament\Pages;

use App\Services\DashboardWidgetService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;
use UnitEnum;

class ManageDashboardSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static string|UnitEnum|null    $navigationGroup = 'Settings';
    protected static ?string                 $navigationLabel = 'Dashboard Widgets';
    protected static ?int                    $navigationSort  = 20;

    protected string $view = 'filament.pages.manage-dashboard-settings';

    public string $selectedRole  = '';
    public array  $widgetSettings = [];

    // ── Boot ──────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $firstRole = Role::orderBy('name')->value('name') ?? 'super_admin';

        $this->selectedRole   = $firstRole;
        $this->widgetSettings = DashboardWidgetService::getSettingsForRole($firstRole);

        $this->form->fill([
            'selectedRole'   => $this->selectedRole,
            'widgetSettings' => $this->widgetSettings,
        ]);
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        $roleOptions = Role::orderBy('name')->pluck('name', 'name')->toArray();
        $allWidgets  = DashboardWidgetService::allWidgets();

        // Partition widgets by group
        $groups = [];
        foreach ($allWidgets as $key => $meta) {
            $group = $meta['group'] ?? 'Other';
            $groups[$group][$key] = $meta;
        }

        // Build one Section per group
        $sections = [
            Section::make('Role')
                ->description('Select a role to configure its dashboard.')
                ->schema([
                    Select::make('selectedRole')
                        ->label('Role')
                        ->options($roleOptions)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (string $state, $set) {
                            $settings = DashboardWidgetService::getSettingsForRole($state);
                            foreach ($settings as $key => $enabled) {
                                $set("widgetSettings.{$key}", (bool) $enabled);
                            }
                        })
                        ->columnSpanFull(),
                ]),
        ];

        foreach ($groups as $groupName => $widgets) {
            $toggles = collect($widgets)
                ->map(fn ($meta, $key) =>
                    Toggle::make("widgetSettings.{$key}")
                        ->label($meta['label'])
                        ->helperText($meta['description'])
                        ->inline(false)
                )
                ->values()
                ->toArray();

            $sections[] = Section::make($groupName . ' Widgets')
                ->description($groupName === 'Operations'
                    ? 'Core operational widgets with business logic.'
                    : 'One toggle per module — auto-populated from your Module Manager.')
                ->schema([
                    Grid::make(2)->schema($toggles),
                ]);
        }

        return $schema->components($sections);
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $data     = $this->form->getState();
        $role     = $data['selectedRole']   ?? $this->selectedRole;
        $settings = $data['widgetSettings'] ?? [];

        DashboardWidgetService::saveSettingsForRole($role, $settings);

        Notification::make()
            ->title('Saved')
            ->body("Dashboard widgets for \"{$role}\" updated.")
            ->success()
            ->send();
    }
}
