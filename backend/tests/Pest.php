<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| This file is included before each test file. It bootstraps the Pest
| testing framework and sets a base test case for all tests in the project.
*/

uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

// Custom expectation: check that a JSON response follows the error envelope.
expect()->extend('toBeApiError', function (string $code) {
    return $this
        ->toHaveKey('error')
        ->and($this->value['error'])->toHaveKey('code', $code);
});

// Custom expectation: check paginated list envelope.
expect()->extend('toBePaginatedResponse', function () {
    return $this
        ->toHaveKey('data')
        ->toHaveKey('meta')
        ->and($this->value['meta'])->toHaveKeys(['current_page', 'per_page', 'total', 'last_page']);
});
