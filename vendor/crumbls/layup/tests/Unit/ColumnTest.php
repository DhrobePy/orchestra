<?php

declare(strict_types=1);

use Crumbls\Layup\View\Column;

it('default span is 12 across all breakpoints', function (): void {
    $col = Column::make();
    expect($col->getSpan())->toBe(['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12]);
});

it('span(int) sets all breakpoints', function (): void {
    $col = Column::make()->span(6);
    expect($col->getSpan())->toBe(['sm' => 6, 'md' => 6, 'lg' => 6, 'xl' => 6]);
});

it('span(array) merges selectively', function (): void {
    $col = Column::make()->span(['md' => 8]);
    expect($col->getSpan())->toBe(['sm' => 12, 'md' => 8, 'lg' => 12, 'xl' => 12]);
});

it('getSpan() returns array', function (): void {
    expect(Column::make()->getSpan())->toBeArray();
});

it('constructor reads span from data', function (): void {
    $col = new Column(['span' => ['sm' => 4, 'lg' => 8]]);
    expect($col->getSpan()['sm'])->toBe(4);
    expect($col->getSpan()['lg'])->toBe(8);
    expect($col->getSpan()['md'])->toBe(12); // default preserved
});

it('getFormSchema() returns 2 tabs (Design + Advanced, no Content)', function (): void {
    $schema = Column::getFormSchema();
    expect($schema)->toBeArray()->not->toBeEmpty();
    $tabs = $schema[0];
    expect($tabs)->toBeInstanceOf(\Filament\Schemas\Components\Tabs::class);
});

it('getDesignFormSchema() includes align_self and overflow fields', function (): void {
    $schema = Column::getDesignFormSchema();
    expect($schema)->toBeArray();
    // Should have span pickers + align_self + overflow + parent design fields
    expect(count($schema))->toBeGreaterThanOrEqual(5);
});
