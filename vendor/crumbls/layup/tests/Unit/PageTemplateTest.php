<?php

declare(strict_types=1);

use Crumbls\Layup\Support\PageTemplate;

it('loads built-in templates', function (): void {
    $templates = PageTemplate::all();
    expect($templates)->toBeArray()
        ->and($templates)->toHaveKey('blank')
        ->and($templates)->toHaveKey('landing-page')
        ->and($templates)->toHaveKey('about')
        ->and($templates)->toHaveKey('contact')
        ->and($templates)->toHaveKey('pricing');
});

it('returns template options for select dropdown', function (): void {
    $options = PageTemplate::options();
    expect($options)->toBeArray()
        ->and($options['blank'])->toBe('Blank Page')
        ->and($options['landing-page'])->toBe('Landing Page');
});

it('gets a single template by slug', function (): void {
    $template = PageTemplate::get('landing-page');
    expect($template)->not->toBeNull()
        ->and($template['name'])->toBe('Landing Page')
        ->and($template['content'])->toHaveKey('rows')
        ->and(count($template['content']['rows']))->toBeGreaterThan(0);
});

it('returns null for unknown template', function (): void {
    expect(PageTemplate::get('nonexistent-xyz'))->toBeNull();
});

it('blank template has empty rows', function (): void {
    $blank = PageTemplate::get('blank');
    expect($blank['content']['rows'])->toBe([]);
});

it('saves a custom template to disk', function (): void {
    $content = ['rows' => [['id' => 'r1', 'settings' => [], 'columns' => []]]];
    $slug = PageTemplate::saveFromPage('Test Template', $content, 'A test');

    expect($slug)->toBe('test-template');

    $path = resource_path('layup/templates/test-template.json');
    expect(file_exists($path))->toBeTrue();

    $loaded = PageTemplate::get('test-template');
    expect($loaded['name'])->toBe('Test Template');
    expect($loaded['description'])->toBe('A test');
    expect($loaded['content'])->toBe($content);

    // Cleanup
    @unlink($path);
    @rmdir(resource_path('layup/templates'));
    @rmdir(resource_path('layup'));
});

it('landing page template has valid widget types', function (): void {
    $template = PageTemplate::get('landing-page');
    $types = [];
    foreach ($template['content']['rows'] as $row) {
        foreach ($row['columns'] as $col) {
            foreach ($col['widgets'] as $widget) {
                $types[] = $widget['type'];
            }
        }
    }
    expect($types)->toContain('heading')
        ->and($types)->toContain('text')
        ->and($types)->toContain('button')
        ->and($types)->toContain('blurb')
        ->and($types)->toContain('cta')
        ->and($types)->toContain('testimonial');
});
