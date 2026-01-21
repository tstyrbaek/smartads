<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySelectionController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        if (!$user) {
            abort(404);
        }

        $companies = $user->companies()->orderBy('name')->get();

        return view('companies.select', [
            'companies' => $companies,
            'activeCompanyId' => (int) $request->session()->get('active_company_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(404);
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $companyId = (int) $validated['company_id'];

        if (!$user->companies()->whereKey($companyId)->exists()) {
            abort(403);
        }

        $request->session()->put('active_company_id', $companyId);

        return redirect()->route('dashboard');
    }
}
