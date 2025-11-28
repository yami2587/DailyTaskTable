<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EmployeeAuthMiddleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // ->withMiddleware(function (Middleware $middleware): void {
    //     //
    // })
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'employee.auth' => EmployeeAuthMiddleware::class,
        ]);
    })


    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
