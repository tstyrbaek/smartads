<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCompany
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($request->routeIs('company.select') || $request->routeIs('company.select.store')) {
            return $next($request);
        }

        $activeCompanyId = (int) $request->session()->get('active_company_id');

        if ($activeCompanyId) {
            if ($user->companies()->whereKey($activeCompanyId)->exists()) {
                return $next($request);
            }

            $request->session()->forget('active_company_id');
        }

        $companyIds = $user->companies()->pluck('companies.id')->all();

        if (count($companyIds) === 1) {
            $request->session()->put('active_company_id', (int) $companyIds[0]);

            return $next($request);
        }

        if (count($companyIds) === 0) {
            abort(403);
        }

        return redirect()->route('company.select');
    }
}
