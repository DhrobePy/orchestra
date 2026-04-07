<?php

declare(strict_types=1);

use Crumbls\Layup\Support\WidgetRegistry;

beforeEach(function (): void {
    $this->registry = new WidgetRegistry;

    // Register all widgets from config
    $widgets = require __DIR__ . '/../../config/layup.php';
    foreach ($widgets['widgets'] ?? [] as $widgetClass) {
        $this->registry->register($widgetClass);
    }
});

it('all registered widget types are unique', function (): void {
    $widgets = $this->registry->all();
    $types = array_map(fn ($w) => $w::getType(), $widgets);

    expect(count($types))->toBe(count(array_unique($types)));
});

it('all registered widget labels are unique', function (): void {
    $widgets = $this->registry->all();
    $labels = array_map(fn ($w) => $w::getLabel(), $widgets);

    expect(count($labels))->toBe(count(array_unique($labels)));
});

it('all widget types are kebab-case', function (): void {
    foreach ($this->registry->all() as $type => $widget) {
        expect($type)->toMatch('/^[a-z][a-z0-9-]*$/', "Widget type '{$type}' should be kebab-case");
    }
});

it('all widget icons start with heroicon-', function (): void {
    foreach ($this->registry->all() as $type => $widget) {
        $icon = $widget::getIcon();
        expect($icon)->toStartWith('heroicon-', "{$type} icon '{$icon}' should start with heroicon-");
    }
});

it('all widget categories are valid', function (): void {
    $valid = ['content', 'media', 'layout', 'interactive', 'advanced'];

    foreach ($this->registry->all() as $type => $widget) {
        expect($widget::getCategory())->toBeIn($valid, "{$type} has invalid category");
    }
});

it('registry has at least 75 widgets from config', function (): void {
    expect(count($this->registry->all()))->toBeGreaterThanOrEqual(75);
});

it('grouped returns at least 3 categories', function (): void {
    $grouped = $this->registry->grouped();
    expect(count($grouped))->toBeGreaterThanOrEqual(3);
});

it('toJs returns correct structure for all widgets', function (): void {
    $js = $this->registry->toJs();

    foreach ($js as $item) {
        expect($item)->toHaveKeys(['type', 'label', 'icon', 'category', 'defaults']);
        expect($item['type'])->toBeString()->not->toBeEmpty();
        expect($item['label'])->toBeString()->not->toBeEmpty();
    }
});

it('every widget getDefaultData returns array', function (): void {
    foreach ($this->registry->all() as $type => $widget) {
        expect($widget::getDefaultData())->toBeArray("{$type}::getDefaultData() should return array");
    }
});

it('every widget getContentFormSchema returns array', function (): void {
    foreach ($this->registry->all() as $type => $widget) {
        expect($widget::getContentFormSchema())->toBeArray("{$type}::getContentFormSchema() should return array");
    }
});
