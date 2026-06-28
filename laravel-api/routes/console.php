<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('images:verify', function () {
    $projectRoot = realpath(base_path('..')) ?: base_path('..');
    $this->info('Project root: '.$projectRoot);
    $this->info('PUBLIC_ASSET_BASE: '.config('app.public_asset_base'));
    $this->newLine();

    $checks = [
        ['table' => 'event', 'label' => 'Courses (event)'],
        ['table' => 'gallery', 'label' => 'Gallery / promotions'],
        ['table' => 'slider', 'label' => 'Sliders'],
    ];

    $missing = 0;
    $ok = 0;

    foreach ($checks as $check) {
        if (! Schema::hasTable($check['table'])) {
            $this->warn("Skip {$check['label']}: table {$check['table']} missing");

            continue;
        }

        $this->line("<fg=cyan>{$check['label']}</> ({$check['table']})");

        $rows = DB::table($check['table'])->select('id', 'name', 'image')->orderByDesc('id')->limit(50)->get();

        foreach ($rows as $row) {
            $image = trim((string) ($row->image ?? ''));
            if ($image === '') {
                $this->line("  [empty] id={$row->id} {$row->name}");
                $missing++;

                continue;
            }

            $relative = $image;
            while (str_starts_with($relative, '../')) {
                $relative = substr($relative, 3);
            }
            if (str_starts_with($relative, 'img/')) {
                $diskPath = $projectRoot.'/'.$relative;
            } else {
                $diskPath = $projectRoot.'/admin/'.ltrim($relative, '/');
            }

            if (is_file($diskPath)) {
                $this->line("  [OK] id={$row->id} {$relative}");
                $ok++;
            } else {
                $this->line("  <fg=red>[MISSING]</> id={$row->id} {$row->name}");
                $this->line("         DB: {$image}");
                $this->line("         Expected: {$diskPath}");
                $missing++;
            }
        }

        $this->newLine();
    }

    $this->info("Summary: {$ok} OK, {$missing} missing/empty");

    return $missing > 0 ? 1 : 0;
})->purpose('List DB image paths that are missing on disk');
