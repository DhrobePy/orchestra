<?php

declare(strict_types=1);

use Crumbls\Layup\LayupPlugin;
use Crumbls\Layup\View\TextWidget;

it('getId() returns layup', function (): void {
    $plugin = new LayupPlugin;
    expect($plugin->getId())->toBe('layup');
});

it('widgets() adds widgets', function (): void {
    $plugin = new LayupPlugin;
    $result = $plugin->widgets([TextWidget::class]);
    expect($result)->toBeInstanceOf(LayupPlugin::class);
});

it('withoutWidgets() removes widgets', function (): void {
    $plugin = new LayupPlugin;
    $result = $plugin->withoutWidgets([TextWidget::class]);
    expect($result)->toBeInstanceOf(LayupPlugin::class);
});

it('withoutConfigWidgets() disables config loading', function (): void {
    $plugin = new LayupPlugin;
    $result = $plugin->withoutConfigWidgets();
    expect($result)->toBeInstanceOf(LayupPlugin::class);
});
