<?php

declare(strict_types=1);

use App\Services\ModuleLoader;
use Illuminate\Support\Facades\File;

it('loads module manifests from Modules directory', function () {
    $loader = new ModuleLoader(base_path('Modules'));
    $all    = $loader->all();

    expect($all)
        ->toBeArray()
        ->toHaveKey('M00Core')
        ->toHaveKey('M01Fleet')
        ->toHaveKey('M02Customers');
});

it('returns a single module manifest', function () {
    $loader   = new ModuleLoader(base_path('Modules'));
    $manifest = $loader->get('M00Core');

    expect($manifest)
        ->toBeArray()
        ->toHaveKey('id', 'M00Core')
        ->toHaveKey('version')
        ->toHaveKey('prerequisites')
        ->toHaveKey('permissions');
});

it('returns null for non-existent module', function () {
    $loader = new ModuleLoader(base_path('Modules'));

    expect($loader->get('M99NonExistent'))->toBeNull();
});

it('validates prerequisites correctly', function () {
    $loader = new ModuleLoader(base_path('Modules'));

    // Should not throw — all prerequisites exist
    expect(fn () => $loader->validatePrerequisites())->not->toThrow(RuntimeException::class);
});

it('detects missing prerequisites', function () {
    $loader = new ModuleLoader(base_path('Modules'));

    // Fake a module that requires a non-existent prerequisite
    $reflection = new ReflectionClass($loader);
    $prop       = $reflection->getProperty('manifests');
    $prop->setAccessible(true);
    $manifests = $prop->getValue($loader);
    $manifests['M99Broken'] = [
        'id'            => 'M99Broken',
        'prerequisites' => ['M98NonExistent'],
    ];
    $prop->setValue($loader, $manifests);

    expect(fn () => $loader->validatePrerequisites())
        ->toThrow(RuntimeException::class, 'M98NonExistent');
});

it('returns modules in topological order', function () {
    $loader  = new ModuleLoader(base_path('Modules'));
    $ordered = $loader->orderedIds();

    // M00Core has no prerequisites so must come before M01Fleet and M02Customers
    $core  = array_search('M00Core', $ordered);
    $fleet = array_search('M01Fleet', $ordered);
    $cust  = array_search('M02Customers', $ordered);

    expect($core)->toBeLessThan($fleet)
        ->and($core)->toBeLessThan($cust);
});

it('collects all permissions from all modules', function () {
    $loader = new ModuleLoader(base_path('Modules'));
    $perms  = $loader->allPermissions();

    expect($perms)
        ->toContain('core.branch.view')
        ->toContain('fleet.vehicle.view')
        ->toContain('customers.customer.view');
});

it('returns only permissions for a specific module', function () {
    $loader = new ModuleLoader(base_path('Modules'));
    $perms  = $loader->permissions('M00Core');

    expect($perms)
        ->toContain('core.branch.view')
        ->not->toContain('fleet.vehicle.view');
});
