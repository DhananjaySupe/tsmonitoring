<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Auth;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API routes
$routes->group('api', ['namespace' => 'App\Controllers'], static function (RouteCollection $routes) {
    // Auth routes with per-route rate limiting
    // login: 10 requests per 60 seconds
    $routes->post('auth/login', 'Auth::login', ['filter' => 'ratelimiter:10,60']);

    $routes->post('auth/logout', 'Auth::logout');

    // forgot-password: 3 requests per 300 seconds (5 minutes)
    $routes->post('auth/forgot-password', 'Auth::forgotPassword', ['filter' => 'ratelimiter:3,300']);

    // register: 5 requests per 300 seconds
    $routes->post('auth/register', 'Auth::register', ['filter' => 'ratelimiter:5,300']);

    // verify-otp: 5 attempts per 300 seconds
    $routes->post('auth/verify-otp', 'Auth::verifyOtp', ['filter' => 'ratelimiter:5,300']);
});
