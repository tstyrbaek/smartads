<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiCompany
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

        $headerCompanyId = $request->header('X-Company-Id');
        $activeCompanyId = $headerCompanyId !== null ? (int) $headerCompanyId : 0;

        if ($activeCompanyId > 0) {
            if (!$user->companies()->whereKey($activeCompanyId)->exists()) {
                abort(403);
            }

            $request->attributes->set('active_company_id', $activeCompanyId);

            return $next($request);
        }

        $companyIds = $user->companies()->pluck('companies.id')->all();

        if (count($companyIds) === 1) {
            $request->attributes->set('active_company_id', (int) $companyIds[0]);

            return $next($request);
        }

        return response()->json([
            'error' => 'company_required',
        ], 422);
    }
}
