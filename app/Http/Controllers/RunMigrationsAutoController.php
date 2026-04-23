<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sleek HTML UI for running pending migrations (same security as RunMigrationsController).
 * Official path: /run-migrations-auto?key=YOUR_SECRET&run=1
 */
class RunMigrationsAutoController extends Controller
{
    private const DEFAULT_SECRET = 'DocuMentoMigrate2026Xp9k3m7';

    public function __invoke(Request $request): Response
    {
        $secret = trim((string) env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET));
        if ($secret === '') {
            $secret = self::DEFAULT_SECRET;
        }

        $provided = trim((string) $request->query('key', ''));
        if ($provided !== $secret) {
            return response()->view('maintenance.run-migrations-auto', [
                'secretOk' => false,
                'ran' => false,
                'output' => '',
                'error' => null,
            ], 403);
        }

        $ran = $request->boolean('run');
        $output = '';
        $error = null;

        if ($ran) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $output .= trim(Artisan::output())."\n\n";
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
                $output .= 'Configuration, route, application, and view caches cleared.';
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return response()->view('maintenance.run-migrations-auto', [
            'secretOk' => true,
            'ran' => $ran,
            'output' => trim($output),
            'error' => $error,
        ], $error ? 500 : 200);
    }
}
