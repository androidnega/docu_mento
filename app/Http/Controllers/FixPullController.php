<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixPullController extends Controller
{
    /** Use same secret as run-migrations / clear-cache; set MIGRATION_RUN_KEY in .env. */
    private const DEFAULT_SECRET = 'DocuMentoMigrate2026Xp9k3m7';

    private function getExpectedKey(): string
    {
        $key = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);

        return trim((string) $key) !== '' ? trim($key) : self::DEFAULT_SECRET;
    }

    private function checkKey(Request $request): bool
    {
        $key = $request->query('key');

        return is_string($key) && trim($key) !== '' && trim($key) === $this->getExpectedKey();
    }

    /**
     * Reset ALL tracked files to HEAD then git pull, so the server always matches the repo.
     * Also clears all Laravel caches and stale AI progress cache entries.
     * Visit: https://your-domain.com/fix-pull/run?key=YOUR_SECRET
     */
    public function run(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            $expected = $this->getExpectedKey();
            $base = $request->getSchemeAndHttpHost();
            $url = $base.'/fix-pull/run?key='.urlencode($expected);

            return response(
                "Invalid or missing key. Add ?key= to the URL.\n\nTry this (default key):\n{$url}\n\nOr set MIGRATION_RUN_KEY in .env and use that value as key=.",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

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

        $body = "Docu Mento: Reset + Update from remote (no merge)\n====================================\n\n";

        // cPanel / crashed git often leaves index.lock; git then refuses every command until it is removed.
        $gitDir = $basePath.DIRECTORY_SEPARATOR.'.git';
        foreach (['index.lock', 'shallow.lock', 'HEAD.lock', 'config.lock'] as $lockName) {
            $lock = $gitDir.DIRECTORY_SEPARATOR.$lockName;
            if (is_file($lock)) {
                if (@unlink($lock)) {
                    $body .= "Removed stale .git/{$lockName} (interrupted git left this file).\n\n";
                } else {
                    $body .= "WARNING: could not delete .git/{$lockName} — remove it in cPanel File Manager, then run this URL again.\n\n";
                }
            }
        }

        // Step 1: fetch from remote
        $cmdFetch = sprintf('cd %s && %s fetch origin 2>&1', escapeshellarg($basePath), escapeshellcmd($git));
        $outFetch = [];
        exec($cmdFetch, $outFetch, $codeFetch);
        $body .= "Step 1: git fetch origin\n";
        $body .= implode("\n", $outFetch)."\n";
        $body .= "Exit code: {$codeFetch}\n\n";

        // Step 2: avoid "fatal: a branch named 'main' already exists" — never use `checkout -b main` when main exists.
        $cd = sprintf('cd %s && ', escapeshellarg($basePath));
        $hasLocalMain = 0;
        exec($cd.$git.' show-ref --verify --quiet refs/heads/main 2>&1', $_, $hasLocalMain);
        $hasOriginMain = 0;
        exec($cd.$git.' show-ref --verify --quiet refs/remotes/origin/main 2>&1', $_, $hasOriginMain);

        $body .= "Step 2: checkout branch (safe for cPanel / existing main)\n";
        $branch = 'main';
        if ($hasOriginMain === 0) {
            if ($hasLocalMain === 0) {
                $cmdCheckout = $cd.$git.' checkout main 2>&1';
            } else {
                $cmdCheckout = $cd.$git.' checkout -b main origin/main 2>&1';
            }
            $outCheckout = [];
            exec($cmdCheckout, $outCheckout, $codeCheckout);
            $body .= implode("\n", $outCheckout)."\n";
            $body .= "Exit code: {$codeCheckout}\n\n";
        } else {
            $outBranch = [];
            exec($cd.$git.' rev-parse --abbrev-ref HEAD 2>&1', $outBranch, $codeBranch);
            $branch = trim(implode('', $outBranch)) ?: 'main';
            $body .= "No origin/main; staying on detected branch: {$branch}\n\n";
        }

        // Step 3: reset hard to remote tip (discards local changes so pull never conflicts)
        $remoteRef = ($hasOriginMain === 0) ? 'main' : $branch;
        $cmdReset = sprintf('%s%s reset --hard origin/%s 2>&1', $cd, escapeshellcmd($git), escapeshellarg($remoteRef));
        $outReset = [];
        exec($cmdReset, $outReset, $codeReset);
        $body .= "Step 3: git reset --hard origin/{$remoteRef}\n";
        $body .= implode("\n", $outReset)."\n";
        $body .= "Exit code: {$codeReset}\n\n";

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

        // Step 4: clear Laravel caches (config, route, view, cache)
        $body .= "Step 4: Clear caches\n";
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            $body .= "Caches cleared.\n\n";
        } catch (\Throwable $e) {
            $body .= 'Cache clear error: '.$e->getMessage()."\n\n";
        }

        $body .= "====================================\n";
        $checkoutOk = ! isset($codeCheckout) || $codeCheckout === 0;
        if ($codeFetch === 0 && $checkoutOk && $codeReset === 0) {
            $body .= "SUCCESS: Code matches remote (origin/{$remoteRef}). Reload the site.\n";
        } else {
            $body .= "WARNING: One or more steps failed. Check output above.\n";
            $body .= "If this URL fails, set MIGRATION_RUN_KEY in .env and use that key in the URL.\n";
        }

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Show maintenance helper links (no key required). Use to verify routes are deployed.
     * Visit: https://your-domain.com/maintenance
     */
    public function maintenance(Request $request): Response
    {
        $base = $request->getSchemeAndHttpHost();
        $key = 'DocuMentoMigrate2026Xp9k3m7';
        $clearCache = $base.'/clear-cache?key='.urlencode($key);
        $fixPullRun = $base.'/fix-pull/run?key='.urlencode($key);
        $thekey = $base.'/thekey?key='.urlencode($key);
        $fixPullPage = $base.'/fix-pull?key='.urlencode($key);
        $runMigrationsAuto = $base.'/run-migrations-auto?key='.urlencode($key);

        $body = "Docu Mento maintenance routes are active.\n\n";
        $body .= "Use these URLs (same key in .env: MIGRATION_RUN_KEY):\n\n";
        $body .= "0. Run migrations (sleek UI; then click Run):\n   {$runMigrationsAuto}\n\n";
        $body .= "1. Clear caches (after deploy):\n   {$clearCache}\n\n";
        $body .= "2. Fix git / cPanel errors (no SSH) – e.g. “branch main already exists”, merge conflicts:\n   {$thekey}\n\n";
        $body .= "3. Same as (2), long URL:\n   {$fixPullRun}\n\n";
        $body .= "4. Fix-pull instructions + script download:\n   {$fixPullPage}\n";

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Show fix-pull instructions and link to download script.
     * Visit: https://your-domain.com/fix-pull?key=YOUR_SECRET
     */
    public function show(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            return response('Invalid or missing key. Use: /fix-pull?key=YOUR_SECRET (set MIGRATION_RUN_KEY in .env).', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $base = $request->getSchemeAndHttpHost();
        $key = $request->query('key');
        $scriptUrl = $base.'/fix-pull/script?key='.urlencode($key);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix git pull – Docu Mento</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        pre { background: #f1f5f9; padding: 1rem; border-radius: 8px; overflow-x: auto; }
        a.dl { display: inline-block; margin-top: 0.5rem; padding: 0.5rem 1rem; background: #0ea5e9; color: #fff; text-decoration: none; border-radius: 6px; }
        a.dl:hover { background: #0284c7; }
        .link { word-break: break-all; color: #0369a1; }
    </style>
</head>
<body>
    <h1>Fix “would be overwritten by merge” (no SSH needed)</h1>
    <p>When cPanel <strong>Git → Pull</strong> fails (e.g. “would be overwritten by merge”, or fatal: a branch named <code>main</code> already exists), open this URL (same key as migrations):</p>
    <p><a class="dl" href="{$base}/fix-pull/run?key={$key}">Run fix-pull now</a></p>
    <p class="link">{$base}/fix-pull/run?key=YOUR_SECRET</p>
    <p>It runs <code>git fetch origin</code> and <code>git reset --hard origin/main</code>. Server local edits are discarded; then cPanel Pull works again.</p>
    <hr>
    <p><strong>If you have SSH</strong>, download and run the script:</p>
    <p><a class="dl" href="{$scriptUrl}">Download fix-pull-on-server.sh</a></p>
    <p class="link">{$scriptUrl}</p>
    <p>Then on the server: <code>chmod +x fix-pull-on-server.sh && ./fix-pull-on-server.sh</code></p>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Serve the fix-pull script for download (same key).
     * Visit: https://your-domain.com/fix-pull/script?key=YOUR_SECRET
     */
    public function script(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            return response('Invalid or missing key.', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $script = <<<'SH'
#!/bin/bash
# Run this on the SERVER when git pull fails with:
#   "Your local changes to the following files would be overwritten by merge"
set -e
echo "Stashing local changes..."
git stash push -m "pre-pull $(date +%Y%m%d-%H%M%S)"
echo "Pulling from remote..."
git pull
echo "Done. To reapply your stashed changes: git stash list && git stash pop"
SH;

        return response($script, 200, [
            'Content-Type' => 'application/x-sh; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="fix-pull-on-server.sh"',
        ]);
    }
}
