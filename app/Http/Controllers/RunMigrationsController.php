<?php

namespace App\Http\Controllers;

use App\Support\MigrationRunnerKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class RunMigrationsController extends Controller
{
    /**
     * Run pending Laravel migrations via URL with a secret key.
     * Visit: https://yoursite.com/run-migrations?key=YOUR_SECRET
     * If MIGRATION_RUN_KEY is empty in .env, ?key= may be omitted (one-click; secret derived from APP_KEY).
     * Fix git pull (no SSH): same URL with &action=fixpull
     * Visit: https://your-domain.com/migration?key=YOUR_SECRET&action=fixpull
     */
    public function __invoke(Request $request): Response
    {
        $provided = $request->query('key');
        if (! MigrationRunnerKey::validate(is_string($provided) ? $provided : null)) {
            $base = $request->getSchemeAndHttpHost();
            $hint = MigrationRunnerKey::oneClickModeEnabled()
                ? "Invalid key. With an empty MIGRATION_RUN_KEY you may omit ?key= or use the derived secret.\nTry: {$base}/run-migrations\n"
                : "Invalid or missing key. Set MIGRATION_RUN_KEY in .env and pass ?key= that value.\n";

            return response($hint, 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        if ($request->query('action') === 'fixpull') {
            return $this->runFixPull();
        }

        $output = "Docu Mento: Run pending Laravel migrations\n";
        $output .= "=======================================\n\n";

        try {
            $output .= "Step 1: Run migrate --force...\n";
            Artisan::call('migrate', ['--force' => true]);
            $output .= trim(Artisan::output())."\n\n";

            $output .= "Step 2: Clear caches...\n";
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            $output .= "Caches cleared.\n\n";

            $output .= "=======================================\n";
            $output .= "SUCCESS: Pending migrations executed.\n";
        } catch (\Throwable $e) {
            $output .= 'ERROR: '.$e->getMessage()."\n";
            $output .= $e->getTraceAsString();
        }

        return response($output, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /** Same logic as FixPullController::run – reset to origin, clear caches (but keep .env). */
    private function runFixPull(): Response
    {
        $basePath = base_path();
        if (! is_dir($basePath.'/.git')) {
            return response("ERROR: .git not found in {$basePath}", 500, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        // Preserve existing .env on the server: back it up before git reset, restore after.
        $envPath = $basePath.'/.env';
        $envExisted = is_file($envPath);
        $envBackup = null;
        if ($envExisted) {
            $envBackup = @file_get_contents($envPath);
        }

        $git = '/usr/local/cpanel/3rdparty/bin/git';
        if (! is_executable($git)) {
            $git = 'git';
        }
        $body = "Docu Mento: Fix pull (reset to remote)\n====================================\n\n";

        // cPanel / crashed git often leaves lock files; clear stale locks before running git commands.
        $gitDir = $basePath.DIRECTORY_SEPARATOR.'.git';
        foreach (['index.lock', 'shallow.lock', 'HEAD.lock', 'config.lock'] as $lockName) {
            $lock = $gitDir.DIRECTORY_SEPARATOR.$lockName;
            if (is_file($lock)) {
                if (@unlink($lock)) {
                    $body .= "Removed stale .git/{$lockName}.\n\n";
                } else {
                    $body .= "WARNING: could not delete .git/{$lockName} — remove it manually, then run this URL again.\n\n";
                }
            }
        }

        // Use exec + exit codes so failures are detectable instead of always reporting success.
        $cd = sprintf('cd %s && ', escapeshellarg($basePath));
        $runExec = function (string $cmd) use ($cd, $git): array {
            $out = [];
            $code = 0;
            exec($cd.escapeshellcmd($git).' '.$cmd.' 2>&1', $out, $code);

            return [implode("\n", $out), $code];
        };

        [$fetchOut, $fetchCode] = $runExec('fetch origin');
        $body .= "Step 1: git fetch origin\n{$fetchOut}\nExit code: {$fetchCode}\n\n";

        // Safe branch handling: avoid "fatal: a branch named 'main' already exists".
        $hasLocalMain = 1;
        exec($cd.$git.' show-ref --verify --quiet refs/heads/main 2>&1', $_, $hasLocalMain);
        $hasOriginMain = 1;
        exec($cd.$git.' show-ref --verify --quiet refs/remotes/origin/main 2>&1', $_, $hasOriginMain);

        $body .= "Step 2: checkout branch (safe for existing main)\n";
        $checkoutCode = 0;
        if ($hasOriginMain === 0) {
            $checkoutCmd = ($hasLocalMain === 0) ? 'checkout main' : 'checkout -b main origin/main';
            [$checkoutOut, $checkoutCode] = $runExec($checkoutCmd);
            $body .= $checkoutOut."\n";
            $body .= "Exit code: {$checkoutCode}\n\n";
        } else {
            $body .= "No origin/main detected; using current branch.\n\n";
        }

        [$branchOut, $branchCode] = $runExec('rev-parse --abbrev-ref HEAD');
        $branch = trim($branchOut) !== '' ? trim($branchOut) : 'main';
        if ($branchCode !== 0) {
            $branch = 'main';
        }

        $remoteRef = ($hasOriginMain === 0) ? 'main' : $branch;
        [$resetOut, $resetCode] = $runExec('reset --hard origin/'.escapeshellarg($remoteRef));
        $body .= "Step 3: git reset --hard origin/{$remoteRef}\n{$resetOut}\nExit code: {$resetCode}\n\n";

        // Restore previous .env after reset so server configuration is not overwritten by repo.
        if ($envExisted && is_string($envBackup)) {
            if (@file_put_contents($envPath, $envBackup) === false) {
                $body .= "WARNING: Failed to restore previous .env after reset; please check file permissions.\n\n";
            } else {
                $body .= "Restored existing .env from before reset.\n\n";
            }
        } elseif ($envExisted && $envBackup === false) {
            $body .= "WARNING: Could not read existing .env before reset; it may have been overwritten.\n\n";
        }

        $body .= "Step 4: Clear caches\n";
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            $body .= "Caches cleared.\n\n";
        } catch (\Throwable $e) {
            $body .= $e->getMessage()."\n\n";
        }
        $body .= "====================================\n";
        if ($fetchCode === 0 && $checkoutCode === 0 && $resetCode === 0) {
            $body .= "SUCCESS: Code matches remote (origin/{$remoteRef}).\n";
        } else {
            $body .= "WARNING: One or more git steps failed. Check output above.\n";
        }

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
