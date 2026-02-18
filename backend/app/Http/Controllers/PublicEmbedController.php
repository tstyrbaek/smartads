<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\IntegrationDefinition;
use App\Models\IntegrationInstance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicEmbedController extends Controller
{
    public function script(IntegrationInstance $instance): Response
    {
        $token = (string) ($instance->config['embed_token'] ?? '');

        $js = "(function(s){\n" .
            "  function load(){\n" .
            "    var container=document.createElement('div');\n" .
            "    container.setAttribute('data-smartads-embed','" . (int) $instance->id . "');\n" .
            "    if(s&&s.parentNode){s.parentNode.insertBefore(container,s);}else{document.body.appendChild(container);}\n" .
            "    var url='" . route('embed.render', ['instance' => $instance->id]) . "?token=" . rawurlencode($token) . "';\n" .
            "    fetch(url).then(function(r){return r.text();}).then(function(html){container.innerHTML=html;}).catch(function(){});\n" .
            "  }\n" .
            "  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',load);}else{load();}\n" .
            "})(document.currentScript);";

        return response($js, 200)->header('Content-Type', 'application/javascript; charset=UTF-8');
    }

    public function render(Request $request, IntegrationInstance $instance): Response
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = (string) ($instance->config['embed_token'] ?? '');
        if ($token === '' || !hash_equals($token, (string) $request->query('token'))) {
            abort(404);
        }

        $instance->loadMissing(['company']);

        $expectedW = null;
        $expectedH = null;

        if ((string) $instance->integration_key === 'website_embed') {
            $config = is_array($instance->config) ? $instance->config : [];
            $expectedW = isset($config['ad_width']) && is_numeric($config['ad_width']) ? (int) $config['ad_width'] : null;
            $expectedH = isset($config['ad_height']) && is_numeric($config['ad_height']) ? (int) $config['ad_height'] : null;
        } else {
            $definition = IntegrationDefinition::query()
                ->where('key', (string) $instance->integration_key)
                ->where('is_active', true)
                ->first();

            $caps = $definition && is_array($definition->capabilities) ? $definition->capabilities : [];
            $expectedW = isset($caps['ad_width']) && is_numeric($caps['ad_width']) ? (int) $caps['ad_width'] : null;
            $expectedH = isset($caps['ad_height']) && is_numeric($caps['ad_height']) ? (int) $caps['ad_height'] : null;
        }

        $adsQuery = $instance->ads()->orderByDesc('created_at');
        if ($expectedW && $expectedH) {
            $adsQuery->where('image_width', $expectedW)->where('image_height', $expectedH);
        }

        $ads = $adsQuery->get();

        $html = view('embed.instance', [
            'ads' => $ads,
            'instance' => $instance,
        ])->render();

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Vary', 'Origin');
    }

    public function networkScript(string $publicId): Response
    {
        if (!Str::isUuid($publicId)) {
            abort(404);
        }

        $js = "(function(s){\n" .
            "  function load(){\n" .
            "    var container=document.createElement('div');\n" .
            "    container.setAttribute('data-smartads-network-embed','" . addslashes($publicId) . "');\n" .
            "    if(s&&s.parentNode){s.parentNode.insertBefore(container,s);}else{document.body.appendChild(container);}\n" .
            "    var url='" . route('network-embed.render', ['publicId' => $publicId]) . "';\n" .
            "    fetch(url).then(function(r){return r.text();}).then(function(html){container.innerHTML=html;}).catch(function(){});\n" .
            "  }\n" .
            "  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',load);}else{load();}\n" .
            "})(document.currentScript);";

        return response($js, 200)->header('Content-Type', 'application/javascript; charset=UTF-8');
    }

    public function networkRender(Request $request, string $publicId): Response
    {
        if (!Str::isUuid($publicId)) {
            abort(404);
        }

        $definition = IntegrationDefinition::query()
            ->where('type', 'network_website_embed')
            ->where('is_active', true)
            ->where('capabilities->embed_public_id', $publicId)
            ->firstOrFail();

        $caps = is_array($definition->capabilities) ? $definition->capabilities : [];
        $expectedW = isset($caps['ad_width']) && is_numeric($caps['ad_width']) ? (int) $caps['ad_width'] : null;
        $expectedH = isset($caps['ad_height']) && is_numeric($caps['ad_height']) ? (int) $caps['ad_height'] : null;

        $adsQuery = Ad::query()
            ->where('status', 'success')
            ->whereNotNull('local_file_path')
            ->whereHas('integrationInstances', function (Builder $q) use ($definition) {
                $q->where('integration_key', $definition->key)
                    ->where('is_active', true)
                    ->whereNotNull('ad_integration_instance.published_at');
            })
            ->with(['company'])
            ->orderByDesc('created_at');

        if ($expectedW && $expectedH) {
            $adsQuery->where('image_width', $expectedW)->where('image_height', $expectedH);
        }

        $ads = $adsQuery->limit(200)->get();

        $html = view('embed.network', [
            'ads' => $ads,
            'definition' => $definition,
        ])->render();

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Vary', 'Origin');
    }
}
