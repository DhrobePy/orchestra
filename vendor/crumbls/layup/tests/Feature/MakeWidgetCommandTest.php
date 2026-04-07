<?php

declare(strict_types=1);

it('scaffolds a new widget class and blade', function (): void {
    $phpPath = app_path('Layup/Widgets/TestBannerWidget.php');
    $bladePath = resource_path('views/components/layup/test-banner.blade.php');

    // Clean up if exists
    @unlink($phpPath);
    @unlink($bladePath);

    $this->artisan('layup:make-widget', ['name' => 'TestBanner'])
        ->assertSuccessful();

    expect(file_exists($phpPath))->toBeTrue();
    expect(file_exists($bladePath))->toBeTrue();

    $phpContent = file_get_contents($phpPath);
    expect($phpContent)->toContain('class TestBannerWidget extends BaseWidget')
        ->and($phpContent)->toContain("return 'test-banner'");

    // Cleanup
    @unlink($phpPath);
    @unlink($bladePath);
    @rmdir(app_path('Layup/Widgets'));
    @rmdir(app_path('Layup'));
    @rmdir(resource_path('views/components/layup'));
});

it('fails if widget already exists', function (): void {
    $phpPath = app_path('Layup/Widgets/DuplicateWidget.php');
    $dir = dirname($phpPath);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($phpPath, '<?php // existing');

    $this->artisan('layup:make-widget', ['name' => 'Duplicate'])
        ->assertFailed();

    @unlink($phpPath);
    @rmdir(app_path('Layup/Widgets'));
    @rmdir(app_path('Layup'));
});

it('auto-appends Widget suffix', function (): void {
    $phpPath = app_path('Layup/Widgets/AutoSuffixWidget.php');
    @unlink($phpPath);

    $this->artisan('layup:make-widget', ['name' => 'AutoSuffix'])
        ->assertSuccessful();

    expect(file_exists($phpPath))->toBeTrue();

    @unlink($phpPath);
    @unlink(resource_path('views/components/layup/auto-suffix.blade.php'));
    @rmdir(app_path('Layup/Widgets'));
    @rmdir(app_path('Layup'));
    @rmdir(resource_path('views/components/layup'));
});
