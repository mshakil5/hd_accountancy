<?php

/**
 * One-time migration repair for databases that were created outside of
 * migrations (SQL imports / manual setup), leaving Laravel's `migrations`
 * table out of sync. It marks migrations whose schema ALREADY EXISTS as
 * "ran" by inserting bookkeeping rows — nothing is dropped or rolled back.
 *
 * Usage (from the hd_backend directory on any machine):
 *   Preview only (no writes):  DRY_RUN=1 php artisan tinker fix_migrations.php
 *   Apply:                     php artisan tinker fix_migrations.php
 *
 * Afterwards `php artisan migrate` will work normally, applying only what
 * is genuinely missing.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$dryRun = in_array(strtolower((string) getenv('DRY_RUN')), ['1', 'true', 'yes'], true);

$files    = glob(database_path('migrations/*.php'));
$names    = array_map(fn($f) => basename($f, '.php'), $files);
$recorded = DB::table('migrations')->pluck('migration')->all();
$pending  = array_values(array_diff($names, $recorded));

$batch = ((int) DB::table('migrations')->max('batch')) + 1;

$mark  = [];
$leave = [];

foreach ($pending as $name) {
    // Filenames carry a timestamp prefix: 2024_09_24_060649_create_x_table
    $key = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $name);
    $path = database_path('migrations/' . $name . '.php');

    // 1. create_X_table -> applied when table X exists.
    if (preg_match('/^create_(.+)_table$/', $key, $m) && is_file($path)) {
        if (Schema::hasTable($m[1])) {
            $mark[] = $name;
        } else {
            $leave[] = "{$name}  [table {$m[1]} missing -> migrate will create it]";
        }
        continue;
    }

    // 2. add/change_X migrations -> applied when every column the file adds
    //    already exists on the target table (drop-only changes: unknown,
    //    left for migrate, which now runs defensively).
    $src = is_file($path) ? file_get_contents($path) : '';
    if (preg_match('/Schema::table\([\'"]([^\'"]+)[\'"]/', $src, $t)
        && preg_match_all('/\$table->(?:unsignedBigInteger|unsignedInteger|unsignedTinyInteger|bigInteger|integer|tinyInteger|string|text|longText|boolean|uuid|date|dateTime|timestamp|decimal|float|double|json|enum|char|time)\([\'"]([^\'"]+)[\'"]/', $src, $cols)
    ) {
        $table  = $t[1];
        $needed = array_values(array_unique($cols[1]));
        $missing = array_filter($needed, fn($c) => !Schema::hasColumn($table, $c));
        if (!$missing && $needed) {
            $mark[] = $name;
        } elseif ($missing) {
            $leave[] = "{$name}  [missing " . implode(', ', array_map(fn($c) => "{$table}.{$c}", $missing)) . ' -> migrate will add]';
        } else {
            $leave[] = "{$name}  [no added columns detected -> migrate will run it]";
        }
        continue;
    }

    $leave[] = "{$name}  [unknown type -> migrate will run it]";
}

if ($dryRun) {
    echo "[DRY RUN] No rows written.\n";
} else {
    foreach ($mark as $n) {
        DB::table('migrations')->insert(['migration' => $n, 'batch' => $batch]);
    }
    echo 'Marked as already applied (' . count($mark) . "), batch {$batch}:\n";
    foreach ($mark as $n) {
        echo "  + {$n}\n";
    }
}

echo "\nLeft pending for migrate to apply (" . count($leave) . "):\n";
foreach ($leave as $n) {
    echo "  ~ {$n}\n";
}
