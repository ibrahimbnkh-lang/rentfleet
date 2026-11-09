<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Models\Setting;
use Modules\M00Core\Services\AuditService;

class SettingController extends ApiController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Setting::query();

        if ($request->filled('group')) {
            $query->where('group', $request->string('group'));
        }

        $settings = $query->get()->pluck('value', 'key');

        return $this->ok($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['nullable', 'string'],
        ]);

        $updated = [];
        $oldValues = [];

        foreach ($validated['settings'] as $item) {
            $setting = Setting::firstOrNew(['key' => $item['key']]);
            $oldValues[$item['key']] = $setting->value;

            $setting->value = is_array($item['value']) ? json_encode($item['value']) : (string) $item['value'];
            if (! empty($item['group'])) {
                $setting->group = $item['group'];
            }
            $setting->save();

            $updated[$item['key']] = $setting->value;
        }

        $this->auditService->log(
            module: 'M00Core',
            action: 'settings.updated',
            oldValues: $oldValues,
            newValues: $updated
        );

        return $this->ok($updated);
    }
}
