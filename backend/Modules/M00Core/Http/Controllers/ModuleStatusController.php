<?php

declare(strict_types=1);

namespace Modules\M00Core\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\M00Core\Services\ModuleRegistry;

class ModuleStatusController extends ApiController
{
    public function index(ModuleRegistry $registry): JsonResponse
    {
        return $this->ok($registry->getStatuses());
    }
}
