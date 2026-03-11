<?php

namespace App\Jobs;

use App\Models\Ad;
use App\Models\IntegrationInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostAdToFacebookPageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly string $adId,
        public readonly int $integrationInstanceId,
    ) {
    }

    public function handle(): void
    {
        $ad = Ad::query()->with('integrationInstances')->find($this->adId);
        if (!$ad) {
            return;
        }

        $instance = IntegrationInstance::query()->find($this->integrationInstanceId);
        if (!$instance) {
            return;
        }

        if ((string) $instance->integration_key !== 'facebook_page') {
            return;
        }

        if ((int) $instance->company_id !== (int) $ad->company_id) {
            return;
        }

        if (!$instance->is_active) {
            return;
        }

        $config = is_array($instance->config) ? $instance->config : [];
        $credentials = is_array($instance->credentials) ? $instance->credentials : [];

        $pageId = trim((string) ($config['page_id'] ?? ''));
        $pageAccessToken = trim((string) ($credentials['page_access_token'] ?? ''));

        if ($pageId === '' || $pageAccessToken === '') {
            $this->markFailed($ad, $instance, 'facebook_not_connected', null);
            return;
        }

        $localPath = trim((string) ($ad->local_file_path ?? ''));
        if ($localPath === '') {
            $this->markFailed($ad, $instance, 'ad_has_no_image', null);
            return;
        }

        $publicPath = Storage::url($localPath);
        $baseUrl = rtrim((string) config('app.url'), '/');
        $imageUrl = str_starts_with($publicPath, 'http://') || str_starts_with($publicPath, 'https://')
            ? $publicPath
            : ($baseUrl . $publicPath);

        $text = trim((string) ($ad->text ?? ''));
        $targetUrl = trim((string) ($ad->target_url ?? ''));

        $caption = $text;
        if ($targetUrl !== '') {
            $caption = trim($caption . "\n\n" . $targetUrl);
        }

        $ad->integrationInstances()->updateExistingPivot($instance->id, [
            'status' => 'publishing',
            'meta' => null,
        ]);

        /** @var Response $res */
        $res = Http::asForm()->post('https://graph.facebook.com/v19.0/' . $pageId . '/photos', [
            'url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $pageAccessToken,
        ]);

        if ($res->getStatusCode() < 200 || $res->getStatusCode() >= 300) {
            $body = (string) $res->body();
            Log::warning('facebook_post_failed', [
                'ad_id' => $ad->id,
                'integration_instance_id' => $instance->id,
                'status' => $res->getStatusCode(),
                'body' => $body,
            ]);
            $this->markFailed($ad, $instance, 'facebook_post_failed', $body);
            return;
        }

        $obj = $res->object();
        $arr = is_object($obj) ? (array) $obj : [];
        $postId = trim((string) ($arr['id'] ?? ''));

        $ad->integrationInstances()->updateExistingPivot($instance->id, [
            'status' => 'published',
            'published_at' => now(),
            'meta' => [
                'facebook_post_id' => $postId !== '' ? $postId : null,
            ],
        ]);
    }

    private function markFailed(Ad $ad, IntegrationInstance $instance, string $error, ?string $responseBody): void
    {
        $ad->integrationInstances()->updateExistingPivot($instance->id, [
            'status' => 'failed',
            'meta' => [
                'error' => $error,
                'response' => $responseBody,
            ],
        ]);
    }
}
