<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronQueueController extends Controller
{
    public function run(Request $request)
    {
        $expected = (string) config('smartads.cron_queue_token', '');
        $provided = (string) $request->query('token', '');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            abort(403);
        }

        $lockPath = storage_path('framework/cron-queue.lock');
        $handle = @fopen($lockPath, 'c');
        if (!$handle) {
            return response()->json(['ok' => false, 'error' => 'lock_open_failed'], 500);
        }

        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                return response()->json(['ok' => true, 'status' => 'already_running']);
            }

            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-time' => 55,
                '--sleep' => 1,
                '--tries' => 3,
                '--timeout' => 90,
                '--no-interaction' => true,
            ]);

            return response()->json([
                'ok' => true,
                'status' => 'ran',
                'output' => Artisan::output(),
            ]);
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }
}
