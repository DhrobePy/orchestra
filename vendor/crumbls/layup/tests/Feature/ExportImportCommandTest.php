<?php

declare(strict_types=1);

use Crumbls\Layup\Models\Page;

it('exports pages as JSON', function (): void {
    Page::create(['title' => 'Export Test', 'slug' => 'export-test', 'content' => ['rows' => []], 'status' => 'published']);

    $this->artisan('layup:export', ['--output' => storage_path('test-export.json'), '--pretty' => true])
        ->assertSuccessful();

    $data = json_decode(file_get_contents(storage_path('test-export.json')), true);
    expect($data)->toHaveKey('pages')
        ->and($data['pages'])->not->toBeEmpty();

    @unlink(storage_path('test-export.json'));
});

it('exports only published pages with status filter', function (): void {
    Page::create(['title' => 'Pub', 'slug' => 'export-pub', 'content' => ['rows' => []], 'status' => 'published']);
    Page::create(['title' => 'Draft', 'slug' => 'export-draft', 'content' => ['rows' => []], 'status' => 'draft']);

    $this->artisan('layup:export', ['--output' => storage_path('test-export2.json'), '--status' => 'draft'])
        ->assertSuccessful();

    $data = json_decode(file_get_contents(storage_path('test-export2.json')), true);
    $slugs = array_column($data['pages'], 'slug');
    expect($slugs)->toContain('export-draft');

    @unlink(storage_path('test-export2.json'));
});

it('imports pages from JSON file', function (): void {
    $export = json_encode([
        'pages' => [
            ['title' => 'Imported', 'slug' => 'imported-page', 'content' => ['rows' => []], 'status' => 'draft'],
        ],
    ]);
    file_put_contents(storage_path('test-import.json'), $export);

    $this->artisan('layup:import', ['file' => storage_path('test-import.json')])
        ->assertSuccessful();

    expect(Page::where('slug', 'imported-page')->exists())->toBeTrue();

    @unlink(storage_path('test-import.json'));
});

it('validates with dry-run without creating', function (): void {
    $export = json_encode([
        'pages' => [
            ['title' => 'DryRun', 'slug' => 'dry-run-test', 'content' => ['rows' => []], 'status' => 'draft'],
        ],
    ]);
    file_put_contents(storage_path('test-dry.json'), $export);

    $this->artisan('layup:import', ['file' => storage_path('test-dry.json'), '--dry-run' => true])
        ->assertSuccessful();

    expect(Page::where('slug', 'dry-run-test')->exists())->toBeFalse();

    @unlink(storage_path('test-dry.json'));
});

it('fails on missing file', function (): void {
    $this->artisan('layup:import', ['file' => '/nonexistent.json'])
        ->assertFailed();
});
