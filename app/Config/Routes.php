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

    // Users (admin/super admin only; token required)
    $routes->get('users', 'Users::index');
    $routes->get('users/view/(:num)', 'Users::view/$1');
    $routes->post('users/new', 'Users::create');
    $routes->post('users/edit/(:num)', 'Users::edit/$1');
    $routes->post('users/delete/(:num)', 'Users::delete/$1');

    $routes->get('user-permissions', 'UserPermissions::index');
    $routes->get('user-permissions/view/(:num)', 'UserPermissions::view/$1');
    $routes->post('user-permissions/new', 'UserPermissions::create');
    $routes->post('user-permissions/edit/(:num)', 'UserPermissions::edit/$1');
    $routes->post('user-permissions/delete/(:num)', 'UserPermissions::delete/$1');

    $routes->get('circles', 'Circles::index');
    $routes->get('circles/view/(:num)', 'Circles::view/$1');
    $routes->post('circles/new', 'Circles::create');
    $routes->post('circles/edit/(:num)', 'Circles::edit/$1');
    $routes->post('circles/delete/(:num)', 'Circles::delete/$1');

    $routes->get('sectors', 'Sectors::index');
    $routes->get('sectors/view/(:num)', 'Sectors::view/$1');
    $routes->post('sectors/new', 'Sectors::create');
    $routes->post('sectors/edit/(:num)', 'Sectors::edit/$1');
    $routes->post('sectors/delete/(:num)', 'Sectors::delete/$1');

    $routes->get('shifts', 'Shifts::index');
    $routes->get('shifts/view/(:num)', 'Shifts::view/$1');
    $routes->post('shifts/new', 'Shifts::create');
    $routes->post('shifts/edit/(:num)', 'Shifts::edit/$1');
    $routes->post('shifts/delete/(:num)', 'Shifts::delete/$1');

    $routes->get('questions', 'Questions::index');
    $routes->get('questions/view/(:num)', 'Questions::view/$1');
    $routes->post('questions/new', 'Questions::create');
    $routes->post('questions/edit/(:num)', 'Questions::edit/$1');
    $routes->post('questions/delete/(:num)', 'Questions::delete/$1');

    $routes->get('sanitation-assets', 'SanitationAssets::index');
    $routes->get('sanitation-assets/view/(:num)', 'SanitationAssets::view/$1');
    $routes->post('sanitation-assets/new', 'SanitationAssets::create');
    $routes->post('sanitation-assets/edit/(:num)', 'SanitationAssets::edit/$1');
    $routes->post('sanitation-assets/delete/(:num)', 'SanitationAssets::delete/$1');

    $routes->get('asset-types', 'AssetTypes::index');
    $routes->get('asset-types/view/(:num)', 'AssetTypes::view/$1');
    $routes->post('asset-types/new', 'AssetTypes::create');
    $routes->post('asset-types/edit/(:num)', 'AssetTypes::edit/$1');
    $routes->post('asset-types/delete/(:num)', 'AssetTypes::delete/$1');

    $routes->get('vendors', 'Vendors::index');
    $routes->get('vendors/view/(:num)', 'Vendors::view/$1');
    $routes->post('vendors/new', 'Vendors::create');
    $routes->post('vendors/edit/(:num)', 'Vendors::edit/$1');
    $routes->post('vendors/delete/(:num)', 'Vendors::delete/$1');

    $routes->get('sanitation-asset-allocations', 'SanitationAssetAllocations::index');
    $routes->get('sanitation-asset-allocations/view/(:num)', 'SanitationAssetAllocations::view/$1');
    $routes->post('sanitation-asset-allocations/new', 'SanitationAssetAllocations::create');
    $routes->post('sanitation-asset-allocations/edit/(:num)', 'SanitationAssetAllocations::edit/$1');
    $routes->post('sanitation-asset-allocations/delete/(:num)', 'SanitationAssetAllocations::delete/$1');

    $routes->get('get-allocations', 'SanitationAssetAllocations::getallocations');
    $routes->get('get-allocation-details/(:num)', 'SanitationAssetAllocations::allocationDetails/$1');
});
