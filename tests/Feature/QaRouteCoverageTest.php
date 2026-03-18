<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * QA Route Coverage Test (Zero-Regression).
 * Asserts that every route name referenced in Blade views is registered.
 * Does not modify any application code.
 */
class QaRouteCoverageTest extends TestCase
{
    public function test_all_route_names_referenced_in_views_are_registered(): void
    {
        $routeNamesFromViews = $this->collectRouteNamesFromViews();
        $registered = collect(Route::getRoutes())->map->getName()->filter()->unique()->flip();

        $missing = [];
        foreach (array_keys($routeNamesFromViews) as $name) {
            if (!$registered->has($name)) {
                $missing[] = $name;
            }
        }

        $this->assertEmpty(
            $missing,
            'Route names used in views but not defined: ' . implode(', ', $missing)
        );
    }

    private function collectRouteNamesFromViews(): array
    {
        $names = [];
        $viewsPath = base_path('resources/views');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'blade.php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (preg_match_all("/route\s*\(\s*['\"]([a-z0-9_.]+)['\"]/", $content, $m)) {
                foreach ($m[1] as $name) {
                    $names[$name] = ($names[$name] ?? 0) + 1;
                }
            }
        }

        return $names;
    }
}
