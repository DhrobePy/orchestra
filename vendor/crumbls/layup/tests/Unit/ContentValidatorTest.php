<?php

declare(strict_types=1);

use Crumbls\Layup\Support\ContentValidator;

beforeEach(function (): void {
    $this->validator = new ContentValidator;
});

it('valid content structure passes', function (): void {
    $content = [
        'rows' => [
            [
                'columns' => [
                    [
                        'widgets' => [
                            ['type' => 'text', 'data' => ['content' => 'hi']],
                        ],
                    ],
                ],
            ],
        ],
    ];
    expect($this->validator->validate($content)->passes())->toBeTrue();
});

it('missing rows key fails', function (): void {
    $result = $this->validator->validate(['something' => 'else']);
    expect($result->passes())->toBeFalse();
    expect($result->errors())->toContain('Missing "rows" key.');
});

it('row without columns fails', function (): void {
    $result = $this->validator->validate(['rows' => [['id' => 'r1']]]);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('missing "columns"');
});

it('column without widgets fails', function (): void {
    $result = $this->validator->validate([
        'rows' => [['columns' => [['id' => 'c1']]]],
    ]);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('missing "widgets"');
});

it('widget without type fails', function (): void {
    $result = $this->validator->validate([
        'rows' => [['columns' => [['widgets' => [['data' => []]]]]]],
    ]);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('missing "type"');
});

it('returns error messages array', function (): void {
    $result = $this->validator->validate(['rows' => [['id' => 'r1']]]);
    expect($result->errors())->toBeArray()->not->toBeEmpty();
});

it('non-array content fails', function (): void {
    $result = $this->validator->validate('not an array');
    expect($result->passes())->toBeFalse();
});

it('strict mode rejects unknown widget types', function (): void {
    $strict = new ContentValidator(strict: true);

    // Register known widgets
    $registry = app(\Crumbls\Layup\Support\WidgetRegistry::class);
    foreach (config('layup.widgets', []) as $class) {
        if (class_exists($class) && ! $registry->has($class::getType())) {
            $registry->register($class);
        }
    }

    $content = [
        'rows' => [['columns' => [['widgets' => [
            ['type' => 'nonexistent-type-xyz', 'data' => []],
        ]]]]],
    ];

    $result = $strict->validate($content);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('unknown widget type');
});

it('strict mode accepts known widget types', function (): void {
    $strict = new ContentValidator(strict: true);

    $registry = app(\Crumbls\Layup\Support\WidgetRegistry::class);
    foreach (config('layup.widgets', []) as $class) {
        if (class_exists($class) && ! $registry->has($class::getType())) {
            $registry->register($class);
        }
    }

    $content = [
        'rows' => [['columns' => [['widgets' => [
            ['type' => 'text', 'data' => ['content' => 'valid']],
        ]]]]],
    ];

    expect($strict->validate($content)->passes())->toBeTrue();
});

it('empty widget type string fails', function (): void {
    $result = $this->validator->validate([
        'rows' => [['columns' => [['widgets' => [
            ['type' => '', 'data' => []],
        ]]]]],
    ]);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('"type" must be a non-empty string');
});

it('non-array rows fails', function (): void {
    $result = $this->validator->validate(['rows' => 'not-array']);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('"rows" must be an array');
});

it('non-array columns fails', function (): void {
    $result = $this->validator->validate(['rows' => [['columns' => 'bad']]]);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('"columns" must be an array');
});

it('non-array widgets fails', function (): void {
    $result = $this->validator->validate(['rows' => [['columns' => [['widgets' => 'bad']]]]]);
    expect($result->passes())->toBeFalse();
    expect($result->errors()[0])->toContain('"widgets" must be an array');
});

it('collects multiple errors across rows and columns', function (): void {
    $content = [
        'rows' => [
            ['columns' => [['widgets' => [['data' => []]]]]],  // missing type
            ['id' => 'r2'],  // missing columns
        ],
    ];

    $result = $this->validator->validate($content);
    expect($result->passes())->toBeFalse();
    expect(count($result->errors()))->toBe(2);
});
