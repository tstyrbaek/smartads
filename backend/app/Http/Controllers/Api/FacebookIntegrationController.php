<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationInstance;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookIntegrationController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $validated = $request->validate([
            'instance_id' => ['required', 'integer', 'exists:integration_instances,id'],
            'return_to' => ['nullable', 'string', 'max:255'],
        ]);

        $instanceId = (int) $validated['instance_id'];
        $returnTo = (string) ($validated['return_to'] ?? '/company');

        $instance = IntegrationInstance::query()
            ->where('company_id', $companyId)
            ->where('integration_key', 'facebook_page')
            ->findOrFail($instanceId);

        $appId = (string) config('services.facebook.app_id', '');
        $redirectUri = (string) config('services.facebook.redirect_uri', '');

        if (trim($appId) === '' || trim($redirectUri) === '') {
            return response()->json(['error' => 'facebook_not_configured'], 500);
        }

        $state = Str::random(48);

        Cache::put('fb_oauth_state_' . $state, [
            'company_id' => $companyId,
            'instance_id' => $instance->id,
            'return_to' => $returnTo,
        ], now()->addMinutes(10));

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'scope' => implode(',', [
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_posts',
            ]),
        ]);

        $url = 'https://www.facebook.com/v19.0/dialog/oauth?' . $query;

        return response()->json(['url' => $url]);
    }

    public function callback(Request $request)
    {
        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');
        $error = (string) $request->query('error', '');

        $stateData = is_string($state) && $state !== '' ? Cache::get('fb_oauth_state_' . $state) : null;
        Cache::forget('fb_oauth_state_' . $state);

        $returnTo = is_array($stateData) && isset($stateData['return_to']) ? (string) $stateData['return_to'] : '/company';

        if ($error !== '' || $code === '' || !is_array($stateData)) {
            return redirect()->to($this->appendQueryParam($returnTo, 'fb_error', $error !== '' ? $error : 'oauth_failed'));
        }

        $appId = (string) config('services.facebook.app_id', '');
        $appSecret = (string) config('services.facebook.app_secret', '');
        $redirectUri = (string) config('services.facebook.redirect_uri', '');

        if (trim($appId) === '' || trim($appSecret) === '' || trim($redirectUri) === '') {
            return redirect()->to($this->appendQueryParam($returnTo, 'fb_error', 'facebook_not_configured'));
        }

        /** @var Response $tokenRes */
        $tokenRes = Http::asForm()->get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'client_secret' => $appSecret,
            'code' => $code,
        ]);

        if ($tokenRes->getStatusCode() < 200 || $tokenRes->getStatusCode() >= 300) {
            return redirect()->to($this->appendQueryParam($returnTo, 'fb_error', 'token_exchange_failed'));
        }

        $tokenObj = $tokenRes->object();
        $tokenArr = is_object($tokenObj) ? (array) $tokenObj : null;
        $shortToken = is_array($tokenArr) ? (string) ($tokenArr['access_token'] ?? '') : '';
        if ($shortToken === '') {
            return redirect()->to($this->appendQueryParam($returnTo, 'fb_error', 'token_missing'));
        }

        /** @var Response $longRes */
        $longRes = Http::asForm()->get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        $longObj = ($longRes->getStatusCode() >= 200 && $longRes->getStatusCode() < 300) ? $longRes->object() : null;
        $longArr = is_object($longObj) ? (array) $longObj : null;
        $userAccessToken = is_array($longArr) ? (string) ($longArr['access_token'] ?? '') : '';
        $expiresIn = is_array($longArr) && isset($longArr['expires_in']) && is_numeric($longArr['expires_in'])
            ? (int) $longArr['expires_in']
            : null;

        if ($userAccessToken === '') {
            $userAccessToken = $shortToken;
        }

        $pages = [];
        $paginationIterations = 0;
        $sawPagingNext = false;
        $nextUrl = 'https://graph.facebook.com/v19.0/me/accounts';
        $query = [
            'access_token' => $userAccessToken,
            'fields' => 'id,name,access_token',
            'limit' => 100,
        ];

        while (is_string($nextUrl) && $nextUrl !== '') {
            $paginationIterations++;
            /** @var Response $pagesRes */
            $pagesRes = Http::get($nextUrl, $query);

            if ($pagesRes->getStatusCode() < 200 || $pagesRes->getStatusCode() >= 300) {
                Log::warning('Facebook pages fetch failed', [
                    'company_id' => (int) ($stateData['company_id'] ?? 0),
                    'instance_id' => (int) ($stateData['instance_id'] ?? 0),
                    'status' => $pagesRes->getStatusCode(),
                    'body' => preg_replace('/"access_token"\s*:\s*"[^"]+"/', '"access_token":"[redacted]"', $pagesRes->body()),
                ]);
                return redirect()->to($this->appendQueryParam($returnTo, 'fb_error', 'pages_fetch_failed'));
            }

            $pagesObj = $pagesRes->object();
            $pagesJson = is_object($pagesObj) ? (array) $pagesObj : null;

            if (is_array($pagesJson) && isset($pagesJson['error'])) {
                Log::warning('Facebook pages fetch returned error payload', [
                    'company_id' => (int) ($stateData['company_id'] ?? 0),
                    'instance_id' => (int) ($stateData['instance_id'] ?? 0),
                    'body' => preg_replace('/"access_token"\s*:\s*"[^"]+"/', '"access_token":"[redacted]"', $pagesRes->body()),
                ]);
                return redirect()->to($this->appendQueryParam($returnTo, 'fb_error', 'pages_fetch_failed'));
            }

            if (is_array($pagesJson) && isset($pagesJson['data']) && is_array($pagesJson['data'])) {
                foreach ($pagesJson['data'] as $p) {
                    if (is_object($p)) {
                        $p = (array) $p;
                    }

                    if (!is_array($p)) {
                        continue;
                    }
                    $id = (string) ($p['id'] ?? '');
                    $name = (string) ($p['name'] ?? '');
                    $pageToken = (string) ($p['access_token'] ?? '');
                    if ($id === '') {
                        continue;
                    }
                    $pages[] = [
                        'id' => $id,
                        'name' => $name,
                        'access_token' => $pageToken,
                    ];
                }
            }

            $pagingRaw = is_array($pagesJson) && isset($pagesJson['paging']) ? $pagesJson['paging'] : null;
            if (is_object($pagingRaw)) {
                $pagingRaw = (array) $pagingRaw;
            }
            $paging = is_array($pagingRaw) ? $pagingRaw : null;

            $nextUrl = is_array($paging) && isset($paging['next']) && is_string($paging['next']) ? $paging['next'] : '';
            if ($nextUrl !== '') {
                $sawPagingNext = true;
            }
            $query = [];
        }

        $uniquePages = [];
        foreach ($pages as $p) {
            if (!is_array($p)) {
                continue;
            }
            $id = (string) ($p['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $uniquePages[$id] = $p;
        }
        $pages = array_values($uniquePages);

        Log::info('Facebook pages fetched', [
            'company_id' => (int) ($stateData['company_id'] ?? 0),
            'instance_id' => (int) ($stateData['instance_id'] ?? 0),
            'count' => count($pages),
            'page_ids' => array_map(static fn ($p) => is_array($p) ? (string) ($p['id'] ?? '') : '', $pages),
            'page_names' => array_map(static fn ($p) => is_array($p) ? (string) ($p['name'] ?? '') : '', $pages),
            'pagination_iterations' => $paginationIterations,
            'saw_paging_next' => $sawPagingNext,
        ]);

        if (empty($pages)) {
            Log::warning('Facebook pages list empty', [
                'company_id' => (int) ($stateData['company_id'] ?? 0),
                'instance_id' => (int) ($stateData['instance_id'] ?? 0),
                'pages_response' => '[]',
            ]);
            return redirect()->to($this->appendQueryParam($returnTo, 'fb_error', 'no_pages'));
        }

        $connectToken = Str::random(48);

        Cache::put('fb_connect_' . $connectToken, [
            'company_id' => (int) ($stateData['company_id'] ?? 0),
            'instance_id' => (int) ($stateData['instance_id'] ?? 0),
            'user_access_token' => $userAccessToken,
            'expires_at' => $expiresIn ? now()->addSeconds($expiresIn)->toISOString() : null,
            'pages' => $pages,
        ], now()->addMinutes(10));

        return redirect()->to($this->appendQueryParam($returnTo, 'fb_connect_token', $connectToken));
    }

    public function resolve(Request $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $validated = $request->validate([
            'connect_token' => ['required', 'string', 'max:255'],
        ]);

        $connectToken = (string) $validated['connect_token'];
        $data = Cache::get('fb_connect_' . $connectToken);

        if (!is_array($data) || (int) ($data['company_id'] ?? 0) !== $companyId) {
            return response()->json(['error' => 'invalid_connect_token'], 422);
        }

        $pages = [];
        foreach (($data['pages'] ?? []) as $p) {
            if (!is_array($p)) {
                continue;
            }
            $pages[] = [
                'id' => (string) ($p['id'] ?? ''),
                'name' => (string) ($p['name'] ?? ''),
            ];
        }

        return response()->json([
            'instance_id' => (int) ($data['instance_id'] ?? 0),
            'pages' => $pages,
        ]);
    }

    public function selectPage(Request $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $validated = $request->validate([
            'connect_token' => ['required', 'string', 'max:255'],
            'page_id' => ['required', 'string', 'max:255'],
        ]);

        $connectToken = (string) $validated['connect_token'];
        $pageId = (string) $validated['page_id'];

        $data = Cache::get('fb_connect_' . $connectToken);
        if (!is_array($data) || (int) ($data['company_id'] ?? 0) !== $companyId) {
            return response()->json(['error' => 'invalid_connect_token'], 422);
        }

        $instanceId = (int) ($data['instance_id'] ?? 0);

        $instance = IntegrationInstance::query()
            ->where('company_id', $companyId)
            ->where('integration_key', 'facebook_page')
            ->findOrFail($instanceId);

        $selected = null;
        foreach (($data['pages'] ?? []) as $p) {
            if (!is_array($p)) {
                continue;
            }
            if ((string) ($p['id'] ?? '') === $pageId) {
                $selected = $p;
                break;
            }
        }

        if (!is_array($selected)) {
            return response()->json(['error' => 'invalid_page'], 422);
        }

        $config = is_array($instance->config) ? $instance->config : [];
        $config['page_id'] = (string) ($selected['id'] ?? '');
        $config['page_name'] = (string) ($selected['name'] ?? '');

        $credentials = is_array($instance->credentials) ? $instance->credentials : [];
        $pageAccessToken = (string) ($selected['access_token'] ?? '');
        $credentials['user_access_token'] = (string) ($data['user_access_token'] ?? '');
        $credentials['expires_at'] = $data['expires_at'] ?? null;

        if ($pageAccessToken === '') {
            $userAccessToken = (string) ($data['user_access_token'] ?? '');
            if ($userAccessToken !== '') {
                /** @var Response $pageRes */
                $pageRes = Http::get('https://graph.facebook.com/v19.0/' . urlencode($pageId), [
                    'fields' => 'access_token',
                    'access_token' => $userAccessToken,
                ]);

                if ($pageRes->getStatusCode() >= 200 && $pageRes->getStatusCode() < 300) {
                    $pageObj = $pageRes->object();
                    $pageArr = is_object($pageObj) ? (array) $pageObj : null;
                    $pageAccessToken = is_array($pageArr) ? (string) ($pageArr['access_token'] ?? '') : '';
                }
            }
        }

        if ($pageAccessToken === '') {
            return response()->json(['error' => 'page_access_token_missing'], 422);
        }

        $credentials['page_access_token'] = $pageAccessToken;

        $instance->forceFill([
            'config' => $config,
            'credentials' => $credentials,
        ])->save();

        Cache::forget('fb_connect_' . $connectToken);

        return response()->json([
            'ok' => true,
            'instance' => $instance->only(['id', 'company_id', 'integration_key', 'name', 'is_active', 'config', 'created_at', 'updated_at']),
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $validated = $request->validate([
            'instance_id' => ['required', 'integer', 'exists:integration_instances,id'],
        ]);

        $instance = IntegrationInstance::query()
            ->where('company_id', $companyId)
            ->where('integration_key', 'facebook_page')
            ->findOrFail((int) $validated['instance_id']);

        $config = is_array($instance->config) ? $instance->config : [];
        unset($config['page_id'], $config['page_name']);

        $instance->forceFill([
            'config' => empty($config) ? null : $config,
            'credentials' => null,
        ])->save();

        return response()->json([
            'ok' => true,
            'instance' => $instance->only(['id', 'company_id', 'integration_key', 'name', 'is_active', 'config', 'created_at', 'updated_at']),
        ]);
    }

    private function appendQueryParam(string $url, string $key, string $value): string
    {
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . urlencode($key) . '=' . urlencode($value);
    }
}
