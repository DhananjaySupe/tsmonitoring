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

    $routes->get('vehicles', 'Vehicles::index');
    $routes->get('vehicles/view/(:num)', 'Vehicles::view/$1');
    $routes->post('vehicles/new', 'Vehicles::create');
    $routes->post('vehicles/edit/(:num)', 'Vehicles::edit/$1');
    $routes->post('vehicles/delete/(:num)', 'Vehicles::delete/$1');

    $routes->get('vehicle-geofences', 'VehicleGeofences::index');
    $routes->get('vehicle-geofences/view/(:num)', 'VehicleGeofences::view/$1');
    $routes->post('vehicle-geofences/new', 'VehicleGeofences::create');
    $routes->post('vehicle-geofences/edit/(:num)', 'VehicleGeofences::edit/$1');
    $routes->post('vehicle-geofences/delete/(:num)', 'VehicleGeofences::delete/$1');

    $routes->get('vehicle-collection-points', 'VehicleCollectionPoints::index');
    $routes->get('vehicle-collection-points/view/(:num)', 'VehicleCollectionPoints::view/$1');
    $routes->post('vehicle-collection-points/new', 'VehicleCollectionPoints::create');
    $routes->post('vehicle-collection-points/edit/(:num)', 'VehicleCollectionPoints::edit/$1');
    $routes->post('vehicle-collection-points/delete/(:num)', 'VehicleCollectionPoints::delete/$1');

    $routes->get('vehicle-daily-trip-summaries', 'VehicleDailyTripSummaries::index');
    $routes->get('vehicle-daily-trip-summaries/view/(:num)', 'VehicleDailyTripSummaries::view/$1');
    $routes->post('vehicle-daily-trip-summaries/new', 'VehicleDailyTripSummaries::create');
    $routes->post('vehicle-daily-trip-summaries/edit/(:num)', 'VehicleDailyTripSummaries::edit/$1');
    $routes->post('vehicle-daily-trip-summaries/delete/(:num)', 'VehicleDailyTripSummaries::delete/$1');

    $routes->get('vehicle-gps-tracking', 'VehicleGpsTracking::index');
    $routes->get('vehicle-gps-tracking/view/(:num)', 'VehicleGpsTracking::view/$1');
    $routes->post('vehicle-gps-tracking/new', 'VehicleGpsTracking::create');
    $routes->post('vehicle-gps-tracking/edit/(:num)', 'VehicleGpsTracking::edit/$1');
    $routes->post('vehicle-gps-tracking/delete/(:num)', 'VehicleGpsTracking::delete/$1');

    $routes->get('vehicle-maintenance-logs', 'VehicleMaintenanceLogs::index');
    $routes->get('vehicle-maintenance-logs/view/(:num)', 'VehicleMaintenanceLogs::view/$1');
    $routes->post('vehicle-maintenance-logs/new', 'VehicleMaintenanceLogs::create');
    $routes->post('vehicle-maintenance-logs/edit/(:num)', 'VehicleMaintenanceLogs::edit/$1');
    $routes->post('vehicle-maintenance-logs/delete/(:num)', 'VehicleMaintenanceLogs::delete/$1');

    $routes->get('vehicle-performance-metrics', 'VehiclePerformanceMetrics::index');
    $routes->get('vehicle-performance-metrics/view/(:num)', 'VehiclePerformanceMetrics::view/$1');
    $routes->post('vehicle-performance-metrics/new', 'VehiclePerformanceMetrics::create');
    $routes->post('vehicle-performance-metrics/edit/(:num)', 'VehiclePerformanceMetrics::edit/$1');
    $routes->post('vehicle-performance-metrics/delete/(:num)', 'VehiclePerformanceMetrics::delete/$1');

    $routes->get('vehicle-routes', 'VehicleRoutes::index');
    $routes->get('vehicle-routes/view/(:num)', 'VehicleRoutes::view/$1');
    $routes->post('vehicle-routes/new', 'VehicleRoutes::create');
    $routes->post('vehicle-routes/edit/(:num)', 'VehicleRoutes::edit/$1');
    $routes->post('vehicle-routes/delete/(:num)', 'VehicleRoutes::delete/$1');

    $routes->get('vehicle-route-assignments', 'VehicleRouteAssignments::index');
    $routes->get('vehicle-route-assignments/view/(:num)', 'VehicleRouteAssignments::view/$1');
    $routes->post('vehicle-route-assignments/new', 'VehicleRouteAssignments::create');
    $routes->post('vehicle-route-assignments/edit/(:num)', 'VehicleRouteAssignments::edit/$1');
    $routes->post('vehicle-route-assignments/delete/(:num)', 'VehicleRouteAssignments::delete/$1');

    $routes->get('vehicle-route-points', 'VehicleRoutePoints::index');
    $routes->get('vehicle-route-points/view/(:num)', 'VehicleRoutePoints::view/$1');
    $routes->post('vehicle-route-points/new', 'VehicleRoutePoints::create');
    $routes->post('vehicle-route-points/edit/(:num)', 'VehicleRoutePoints::edit/$1');
    $routes->post('vehicle-route-points/delete/(:num)', 'VehicleRoutePoints::delete/$1');

    $routes->get('sanitation-asset-allocations', 'SanitationAssetAllocations::index');
    $routes->get('sanitation-asset-allocations/view/(:num)', 'SanitationAssetAllocations::view/$1');
    $routes->post('sanitation-asset-allocations/new', 'SanitationAssetAllocations::create');
    $routes->post('sanitation-asset-allocations/edit/(:num)', 'SanitationAssetAllocations::edit/$1');
    $routes->post('sanitation-asset-allocations/delete/(:num)', 'SanitationAssetAllocations::delete/$1');

    $routes->get('get-allocations', 'SanitationAssetAllocations::getallocations');
    $routes->get('get-allocation-details/(:num)', 'SanitationAssetAllocations::allocationDetails/$1');

    $routes->get('sanitation-inspections', 'SanitationInspections::index');
    $routes->get('sanitation-inspections/view/(:num)', 'SanitationInspections::view/$1');
    $routes->post('sanitation-inspections/new', 'SanitationInspections::create');
    $routes->post('sanitation-inspections/edit/(:num)', 'SanitationInspections::edit/$1');

    $routes->get('sanitation-incidents', 'SanitationIncidents::index');
    $routes->get('sanitation-incidents/view/(:num)', 'SanitationIncidents::view/$1');
    $routes->post('sanitation-incidents/new', 'SanitationIncidents::create');
    $routes->post('sanitation-incidents/edit/(:num)', 'SanitationIncidents::edit/$1');
    $routes->post('sanitation-incidents/close/(:num)', 'SanitationIncidents::close/$1');

    $routes->post('sanitation-asset-tagging', 'AssetTagging::sanitationAssetTagging');

    $routes->post('upload/image', 'Upload::image');
});
