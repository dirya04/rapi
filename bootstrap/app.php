<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (!$request->isMethod('post') || (!$request->is('complaints') && !$request->is('api/complaints'))) {
                return null;
            }

            $message = 'Unggahan melebihi batas server. Pastikan batas upload_max_filesize minimal 10 MB dan post_max_size minimal 12 MB.';

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['photo' => [$message]],
                ], 413);
            }

            return back()->withErrors(['photo' => $message])->withInput();
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
