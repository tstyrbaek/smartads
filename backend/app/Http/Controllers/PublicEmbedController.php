<?php

namespace App\Http\Controllers;

use App\Models\IntegrationInstance;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

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

        $ads = $instance->ads()->orderByDesc('created_at')->get();

        $html = view('embed.instance', [
            'ads' => $ads,
            'instance' => $instance,
        ])->render();

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Vary', 'Origin');
    }
}
