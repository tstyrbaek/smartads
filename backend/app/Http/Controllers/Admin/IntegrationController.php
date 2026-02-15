<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationInstance;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(): View
    {
        return view('admin.integrations.index', [
            'instances' => IntegrationInstance::query()
                ->with(['company'])
                ->orderByDesc('id')
                ->paginate(50),
        ]);
    }
}
