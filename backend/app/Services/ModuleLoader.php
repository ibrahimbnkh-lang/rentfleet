<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * ModuleLoader — reads all module.json manifests from Modules/,
 * validates prerequisites, and decides which modules are active.
 *
 * A module is "active" when:
 *   1. Its module.json exists and is valid.
 *   2. All prerequisite modules are also active.
 *   3. The license service confirms it is licensed (checked at runtime).
 *
 * This class intentionally knows nothing about database or licensing — it
 * only parses manifests. The LicenseService (M00Core) adds the licensed
 * layer on top.
 */
final class ModuleLoader
{
    /** @var array<string, array<string, mixed>> */
    private array $manifests = [];

    private bool $loaded = false;

    public function __construct(
        private readonly string $modulesPath,
    ) {}

    /**
     * Load all manifests from disk (cached in memory for this request).
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (! is_dir($this->modulesPath)) {
            $this->loaded = true;
            return;
        }

        foreach (File::directories($this->modulesPath) as $dir) {
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'module.json';

            if (! file_exists($manifestPath)) {
                continue;
            }

            $json = file_get_contents($manifestPath);
            $data = json_decode($json, true);

            if (! is_array($data) || empty($data['id'])) {
                throw new RuntimeException("Invalid module.json in {$dir}: missing 'id' field.");
            }

            $this->manifests[$data['id']] = $data;
        }

        $this->loaded = true;
    }

    /**
     * Return all manifest data indexed by module ID.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $this->load();

        return $this->manifests;
    }

    /**
     * Return a single module manifest or null.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $moduleId): ?array
    {
        $this->load();

        return $this->manifests[$moduleId] ?? null;
    }

    /**
     * Check if a module exists (regardless of license status).
     */
    public function exists(string $moduleId): bool
    {
        $this->load();

        return isset($this->manifests[$moduleId]);
    }

    /**
     * Return all module IDs in dependency-safe order (topological sort).
     *
     * @return array<string>
     */
    public function orderedIds(): array
    {
        $this->load();

        $sorted   = [];
        $visited  = [];

        foreach (array_keys($this->manifests) as $id) {
            $this->visit($id, $visited, $sorted);
        }

        return $sorted;
    }

    /**
     * Return the permission strings declared by a module.
     *
     * @return array<string>
     */
    public function permissions(string $moduleId): array
    {
        $this->load();

        return $this->manifests[$moduleId]['permissions'] ?? [];
    }

    /**
     * Return ALL permissions across all modules.
     *
     * @return array<string>
     */
    public function allPermissions(): array
    {
        $this->load();

        $perms = [];
        foreach ($this->manifests as $manifest) {
            $perms = array_merge($perms, $manifest['permissions'] ?? []);
        }

        return array_unique($perms);
    }

    /**
     * Return prerequisite module IDs for a given module.
     *
     * @return array<string>
     */
    public function prerequisites(string $moduleId): array
    {
        $this->load();

        return $this->manifests[$moduleId]['prerequisites'] ?? [];
    }

    /**
     * Validate that all prerequisite modules exist.
     *
     * @throws RuntimeException if a prerequisite is missing.
     */
    public function validatePrerequisites(): void
    {
        $this->load();

        foreach ($this->manifests as $id => $manifest) {
            foreach (($manifest['prerequisites'] ?? []) as $prereq) {
                if (! isset($this->manifests[$prereq])) {
                    throw new RuntimeException(
                        "Module {$id} requires {$prereq}, but that module is not installed."
                    );
                }
            }
        }
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    /** @param array<string> $visited */
    private function visit(string $id, array &$visited, array &$sorted): void
    {
        if (isset($visited[$id])) {
            return;
        }

        $visited[$id] = true;

        foreach ($this->prerequisites($id) as $prereq) {
            $this->visit($prereq, $visited, $sorted);
        }

        $sorted[] = $id;
    }
}
