<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdSizeService;

class MetaController extends Controller
{
    public function adSizes(AdSizeService $service): array
    {
        return [
            'sizes' => $service->allowedSizes(),
        ];
    }
}
