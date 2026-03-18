<?php

/**
 * QA Route Verification Script (Zero-Regression)
 *
 * Run from project root: php tests/qa/RouteVerificationScript.php
 * Or: php artisan tinker < tests/qa/RouteVerificationScript.php (if using tinker)
 *
 * This script ONLY reads routes and view files; it does not modify any application code.
 * Output: list of route names that are referenced in views but missing in the app.
 */

// Bootstrap Laravel (run from project root: php tests/qa/RouteVerificationScript.php)
$app = require __DIR__ . '/../../vendor/autoload.php';
$app = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$app->bootstrap();

$routeNamesFromViews = [];
$viewsPath = base_path('resources/views');

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsPath, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'blade.php') {
        continue;
    }
    $content = file_get_contents($file->getPathname());
    // Match route('name') or route("name") or route('name', ...)
    if (preg_match_all("/route\s*\(\s*['\"]([a-z0-9_.]+)['\"]/", $content, $m)) {
        foreach ($m[1] as $name) {
            $routeNamesFromViews[$name] = ($routeNamesFromViews[$name] ?? 0) + 1;
        }
    }
}

$registered = collect(\Illuminate\Support\Facades\Route::getRoutes())->map->getName()->filter()->unique()->flip();
$missing = [];
foreach (array_keys($routeNamesFromViews) as $name) {
    if (!$registered->has($name)) {
        $missing[] = $name;
    }
}

echo "=== QA Route Verification (Zero-Regression) ===\n";
echo "Route names referenced in views: " . count($routeNamesFromViews) . "\n";
echo "Registered route names: " . $registered->count() . "\n";
echo "Missing (used in views but not defined): " . count($missing) . "\n";
if (count($missing) > 0) {
    echo "Missing routes:\n";
    foreach ($missing as $r) {
        echo "  - " . $r . "\n";
    }
} else {
    echo "All route names referenced in views are registered.\n";
}
