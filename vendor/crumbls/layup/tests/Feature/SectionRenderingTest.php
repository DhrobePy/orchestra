<?php

declare(strict_types=1);

use Crumbls\Layup\Models\Page;
use Crumbls\Layup\Support\WidgetRegistry;
use Crumbls\Layup\View\TextWidget;

beforeEach(function (): void {
    $registry = app(WidgetRegistry::class);
    if (! $registry->has('text')) {
        $registry->register(TextWidget::class);
    }
});

it('builds section tree from sections key', function (): void {
    $page = Page::create([
        'title' => 'Section Test',
        'slug' => 'section-render-test',
        'content' => [
            'sections' => [
                [
                    'settings' => ['background_color' => '#ff0000', 'min_height' => '50vh'],
                    'rows' => [
                        ['settings' => [], 'columns' => [
                            ['span' => ['sm' => 12], 'settings' => [], 'widgets' => [
                                ['type' => 'text', 'data' => ['content' => 'Hello from section']],
                            ]],
                        ]],
                    ],
                ],
            ],
        ],
        'status' => 'published',
    ]);

    $sections = $page->getSectionTree();
    expect($sections)->toHaveCount(1)
        ->and($sections[0]['settings']['background_color'])->toBe('#ff0000')
        ->and($sections[0]['rows'])->toHaveCount(1);
});

it('flattens sections into getContentTree for backward compat', function (): void {
    $page = Page::create([
        'title' => 'Flat Test',
        'slug' => 'flat-section-test',
        'content' => [
            'sections' => [
                ['settings' => [], 'rows' => [['settings' => [], 'columns' => []]]],
                ['settings' => [], 'rows' => [['settings' => [], 'columns' => []], ['settings' => [], 'columns' => []]]],
            ],
        ],
        'status' => 'published',
    ]);

    $tree = $page->getContentTree();
    expect($tree)->toHaveCount(3); // 1 + 2 rows flattened
});

it('builds section styles correctly', function (): void {
    $styles = \Crumbls\Layup\View\Section::buildSectionStyles([
        'background_color' => '#333',
        'background_gradient' => 'linear-gradient(to right, #000, #fff)',
        'min_height' => '100vh',
    ]);

    expect($styles)->toContain('background-color: #333')
        ->and($styles)->toContain('linear-gradient')
        ->and($styles)->toContain('min-height: 100vh');
});

it('handles empty sections gracefully', function (): void {
    $page = Page::create([
        'title' => 'Empty Sections',
        'slug' => 'empty-sections-test',
        'content' => ['sections' => []],
        'status' => 'published',
    ]);

    expect($page->getSectionTree())->toHaveCount(0)
        ->and($page->getContentTree())->toHaveCount(0);
});
