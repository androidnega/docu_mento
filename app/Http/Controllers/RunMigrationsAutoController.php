<?php

namespace App\Http\Controllers;

use App\Support\MigrationRunnerKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sleek HTML UI for running pending migrations (same rules as RunMigrationsController).
 * One-click: leave MIGRATION_RUN_KEY empty — open /run-migrations-auto then Run (optional ?key= if set).
 */
class RunMigrationsAutoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $provided = $request->query('key');
        $ok = MigrationRunnerKey::validate(is_string($provided) ? $provided : null);
        if (! $ok) {
            return response()->view('maintenance.run-migrations-auto', [
                'secretOk' => false,
                'ran' => false,
                'output' => '',
                'error' => null,
                'oneClickMode' => MigrationRunnerKey::oneClickModeEnabled(),
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
            'oneClickMode' => MigrationRunnerKey::oneClickModeEnabled(),
        ], $error ? 500 : 200);
    }
}
