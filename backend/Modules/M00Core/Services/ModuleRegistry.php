<?php

declare(strict_types=1);

namespace Modules\M00Core\Services;

use App\Services\ModuleLoader;

class ModuleRegistry
{
    public function __construct(
        private readonly ModuleLoader $loader,
        private readonly LicenseService $licenseService,
    ) {}

    /**
     * Check if a module is installed, prerequisites satisfied, and licensed.
     */
    public function isActive(string $moduleId): bool
    {
        if ($moduleId === 'M00Core') {
            return true;
        }

        if (! $this->loader->exists($moduleId)) {
            return false;
        }

        // Check prerequisites
        foreach ($this->loader->prerequisites($moduleId) as $prereq) {
            if (! $this->isActive($prereq)) {
                return false;
            }
        }

        // Check license
        return $this->licenseService->isModuleLicensed($moduleId);
    }

    /**
     * Get statuses of all known modules.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getStatuses(): array
    {
        $manifests = $this->loader->all();
        $statuses = [];

        foreach ($manifests as $id => $manifest) {
            $isCore = ($id === 'M00Core');
            $licensed = $isCore || $this->licenseService->isModuleLicensed($id);
            $active = $this->isActive($id);

            $statuses[$id] = [
                'id' => $id,
                'name' => $manifest['name'] ?? $id,
                'version' => $manifest['version'] ?? '1.0.0',
                'description' => $manifest['description'] ?? '',
                'prerequisites' => $manifest['prerequisites'] ?? [],
                'is_core' => $isCore,
                'is_licensed' => $licensed,
                'is_active' => $active,
            ];
        }

        return $statuses;
    }
}
