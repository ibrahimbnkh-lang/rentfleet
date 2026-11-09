<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehicleEquipment;

class VehicleEquipmentController extends ApiController
{
    public function index(Vehicle $vehicle): JsonResponse
    {
        $equipment = $vehicle->equipment()->get();

        return $this->ok($equipment);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string'],
            'items.*.is_present' => ['required', 'boolean'],
            'items.*.condition' => ['nullable', 'string'],
        ]);

        foreach ($validated['items'] as $item) {
            if (! empty($item['id'])) {
                VehicleEquipment::where('id', $item['id'])
                    ->where('vehicle_id', $vehicle->id)
                    ->update([
                        'is_present' => $item['is_present'],
                        'condition' => $item['condition'] ?? 'bon_etat',
                    ]);
            } else {
                $vehicle->equipment()->create([
                    'name' => $item['name'],
                    'is_present' => $item['is_present'],
                    'condition' => $item['condition'] ?? 'bon_etat',
                ]);
            }
        }

        return $this->ok($vehicle->equipment()->get());
    }
}
