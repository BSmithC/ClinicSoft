<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ZiggyRoutesTest extends TestCase
{
    /**
     * Ziggy throws at render time when a Vue template calls a route name that
     * no longer exists on the server (for example after closing registration).
     */
    public function test_frontend_only_references_existing_routes(): void
    {
        $available = [];

        foreach (Route::getRoutes() as $route) {
            if ($name = $route->getName()) {
                $available[$name] = true;
            }
        }

        $root = resource_path('js');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $missing = [];

        foreach ($files as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), ['js', 'vue'], true)) {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());

            if (preg_match_all("/route\(\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
                foreach ($matches[1] as $name) {
                    if (! isset($available[$name])) {
                        $missing[$name][] = str_replace($root, '', $file->getPathname());
                    }
                }
            }
        }

        $this->assertSame([], $missing, 'Hay llamadas route() en el frontend sin ruta Laravel equivalente.');
    }
}
