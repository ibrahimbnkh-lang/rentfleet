<?php

declare(strict_types=1);

namespace Modules\M01Fleet\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehiclePhoto;

class VehiclePhotoController extends ApiController
{
    public function index(Vehicle $vehicle): JsonResponse
    {
        $photos = $vehicle->photos()->latest()->get();

        return $this->ok($photos);
    }

    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        if ($request->hasFile('photo')) {
            $request->validate([
                'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,avif', 'max:10240'],
                'caption' => ['nullable', 'string', 'max:150'],
                'is_primary' => ['nullable'],
            ]);

            $file = $request->file('photo');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = 'veh_' . $vehicle->id . '_' . time() . '_' . Str::random(8) . '.' . $extension;

            $destDir = public_path('uploads/vehicles');
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $file->move($destDir, $filename);
            $filePath = '/uploads/vehicles/' . $filename;
            $caption = $request->input('caption');
            $isPrimary = filter_var($request->input('is_primary'), FILTER_VALIDATE_BOOLEAN);
        } else {
            $validated = $request->validate([
                'file_path' => ['required', 'string'],
                'caption' => ['nullable', 'string', 'max:150'],
                'is_primary' => ['nullable', 'boolean'],
            ]);

            $filePath = $validated['file_path'];
            $caption = $validated['caption'] ?? null;
            $isPrimary = ! empty($validated['is_primary']);
        }

        // If marked primary or if this is the first photo, ensure it's primary
        $isFirstPhoto = $vehicle->photos()->count() === 0;
        if ($isPrimary || $isFirstPhoto) {
            $vehicle->photos()->update(['is_primary' => false]);
            $isPrimary = true;
        }

        $photo = $vehicle->photos()->create([
            'file_path' => $filePath,
            'caption' => $caption,
            'is_primary' => $isPrimary,
        ]);

        return $this->created($photo);
    }

    public function setPrimary(Vehicle $vehicle, VehiclePhoto $photo): JsonResponse
    {
        $vehicle->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);

        return $this->ok($photo);
    }

    public function destroy(Vehicle $vehicle, VehiclePhoto $photo): JsonResponse
    {
        if (str_starts_with($photo->file_path, '/uploads/')) {
            $localPath = public_path(ltrim($photo->file_path, '/'));
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }

        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($wasPrimary) {
            $vehicle->photos()->first()?->update(['is_primary' => true]);
        }

        return $this->noContent();
    }
}
