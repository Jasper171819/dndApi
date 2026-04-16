<?php

use App\Models\Character;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'initialCharacters' => Character::query()->latest()->get(),
    ]);
})->name('home');

Route::get('/api-overzicht', function () {
    $sampleCharacter = Character::query()->latest()->first();

    $appRoutes = collect(app('router')->getRoutes()->getRoutes())
        ->reject(function ($route) {
            $uri = $route->uri();

            return $uri === 'up' || str_starts_with($uri, 'storage/');
        })
        ->map(function ($route) use ($sampleCharacter) {
            $methods = collect($route->methods())
                ->reject(fn (string $method) => $method === 'HEAD')
                ->values()
                ->all();

            $uri = $route->uri() === '/' ? '/' : '/'.ltrim($route->uri(), '/');
            $examplePath = $uri;

            if ($sampleCharacter !== null) {
                $examplePath = str_replace('{id}', (string) $sampleCharacter->id, $examplePath);
            }

            return [
                'group' => str_starts_with($route->uri(), 'api/') ? 'API' : 'Web',
                'methods' => $methods,
                'uri' => $uri,
                'example_path' => $examplePath,
                'example_url' => url(ltrim($examplePath, '/')),
                'can_open' => in_array('GET', $methods, true) && ! str_contains($examplePath, '{'),
            ];
        })
        ->sortBy(fn (array $route) => $route['group'].' '.implode(',', $route['methods']).' '.$route['uri'])
        ->values();

    return view('api-overzicht', [
        'appRoutes' => $appRoutes,
        'sampleCharacter' => $sampleCharacter,
    ]);
})->name('api.overview');
