<?php

declare(strict_types=1);

use Crumbls\Layup\Models\Page;
use Crumbls\Layup\Support\SafelistCollector;

it('writes safelist file to default path', function (): void {
    $path = SafelistCollector::defaultPath();

    $this->artisan('layup:safelist')
        ->assertSuccessful();

    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toContain('container');

    @unlink($path);
});

it('outputs to stdout with --stdout flag', function (): void {
    $this->artisan('layup:safelist', ['--stdout' => true])
        ->assertSuccessful();
});

it('static-only excludes dynamic classes', function (): void {
    Page::create([
        'title' => 'Static Only Test',
        'slug' => 'static-only-test',
        'content' => ['rows' => [[
            'id' => 'r1',
            'settings' => ['class' => 'should-not-appear-xyz'],
            'columns' => [],
        ]]],
        'status' => 'published',
    ]);

    $this->artisan('layup:safelist', ['--stdout' => true, '--static-only' => true])
        ->assertSuccessful()
        ->doesntExpectOutput('should-not-appear-xyz');
});

it('writes to custom output path', function (): void {
    $relative = 'storage/layup-test-custom-safelist.txt';
    $path = base_path($relative);

    $this->artisan('layup:safelist', ['--output' => $relative])
        ->assertSuccessful();

    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toContain('container');

    @unlink($path);
});
