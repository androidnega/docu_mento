<?php

namespace App\Http\Controllers;

use App\Support\MigrationRunnerKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class ClearCacheController extends Controller
{
    /**
     * Clear Laravel caches via URL with a secret key.
     * Visit: https://YOUR-LIVE-SITE.com/clear-cache?key=YOUR_SECRET
     * If MIGRATION_RUN_KEY is empty, ?key= may be omitted (same one-click rules as migrations).
     */
    public function __invoke(Request $request): Response
    {
        $provided = $request->query('key');
        if (! MigrationRunnerKey::validate(is_string($provided) ? $provided : null)) {
            return response('Invalid or missing key. With MIGRATION_RUN_KEY empty you may omit ?key=. Otherwise set MIGRATION_RUN_KEY in .env.', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $lines = [];
        $lines[] = 'Docu Mento: Clear cache (fix deploy / not showing changes)';
        $lines[] = '========================================================';
        $lines[] = '';

        try {
            $lines[] = 'Running: config:clear';
            Artisan::call('config:clear');
            $lines[] = 'Running: route:clear';
            Artisan::call('route:clear');
            $lines[] = 'Running: view:clear';
            Artisan::call('view:clear');
            $lines[] = 'Running: cache:clear';
            Artisan::call('cache:clear');
            $lines[] = '';
            $lines[] = '========================================================';
            $lines[] = 'SUCCESS: All caches cleared. Reload your site.';
        } catch (\Throwable $e) {
            $lines[] = 'ERROR: '.$e->getMessage();
            $lines[] = $e->getTraceAsString();
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
