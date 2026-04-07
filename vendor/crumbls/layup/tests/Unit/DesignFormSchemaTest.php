<?php

declare(strict_types=1);

use Crumbls\Layup\View\HeadingWidget;
use Crumbls\Layup\View\Row;
use Crumbls\Layup\View\TextWidget;

// Helper to extract names safely from schema components
function schemaNames(array $schema): array
{
    $names = [];
    foreach ($schema as $component) {
        if (method_exists($component, 'getName')) {
            try {
                $names[] = $component->getName();
            } catch (Throwable) {
                // Skip components without getName
            }
        }
    }

    return $names;
}

// ── Design Tab Fields ──

it('design schema has multiple fields', function (): void {
    $schema = Row::getDesignFormSchema();
    expect(count($schema))->toBeGreaterThanOrEqual(10);
});

it('design schema includes text-related fields', function (): void {
    $names = schemaNames(Row::getDesignFormSchema());
    expect($names)->toContain('text_color');
    expect($names)->toContain('text_align');
    expect($names)->toContain('font_size');
});

it('design schema includes border fields', function (): void {
    $names = schemaNames(Row::getDesignFormSchema());
    expect($names)->toContain('border_radius');
    expect($names)->toContain('border_width');
    expect($names)->toContain('border_style');
    expect($names)->toContain('border_color');
});

it('design schema includes visual effect fields', function (): void {
    $names = schemaNames(Row::getDesignFormSchema());
    expect($names)->toContain('box_shadow');
    expect($names)->toContain('opacity');
    expect($names)->toContain('background_color');
});

// ── Advanced Tab Fields ──

it('advanced schema has 6 fields', function (): void {
    $schema = Row::getAdvancedFormSchema();
    expect(count($schema))->toBe(6);
});

it('advanced schema includes id and class fields', function (): void {
    $names = schemaNames(Row::getAdvancedFormSchema());
    expect($names)->toContain('id');
    expect($names)->toContain('class');
});

it('advanced schema includes inline css', function (): void {
    $names = schemaNames(Row::getAdvancedFormSchema());
    expect($names)->toContain('inline_css');
});

it('advanced schema includes hide_on and animation', function (): void {
    $names = schemaNames(Row::getAdvancedFormSchema());
    expect($names)->toContain('hide_on');
    expect($names)->toContain('animation');
    expect($names)->toContain('animation_duration');
});

// ── Form Schema inherits tabs ──

it('widgets inherit design and advanced tabs from BaseView', function (): void {
    $schema = TextWidget::getFormSchema();
    expect($schema)->toBeArray()->not->toBeEmpty();
    expect($schema[0])->toBeInstanceOf(\Filament\Schemas\Components\Tabs::class);
});

it('different widgets share same design schema field count', function (): void {
    $textDesign = TextWidget::getDesignFormSchema();
    $headingDesign = HeadingWidget::getDesignFormSchema();

    expect(count($textDesign))->toBe(count($headingDesign));
});

it('different widgets share same advanced schema field count', function (): void {
    $textAdv = TextWidget::getAdvancedFormSchema();
    $headingAdv = HeadingWidget::getAdvancedFormSchema();

    expect(count($textAdv))->toBe(count($headingAdv));
});
