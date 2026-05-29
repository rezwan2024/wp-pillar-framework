<?php

declare(strict_types=1);

use App\Http\Policies\ExamplePolicy;
use WPPillar\Framework\Http\Router;

/**
 * Example Plugin REST API routes.
 *
 * All routes are protected by ExamplePolicy and have nonce verification
 * built into the Router automatically. This file is required inside a
 * rest_api_init action — see AppServiceProvider::register().
 */
$router = new Router(wpillar_config('rest_namespace'));

// Collection routes
$router->get(  '/examples',               'ExampleController@index',   ExamplePolicy::class);
$router->post( '/examples',               'ExampleController@store',   ExamplePolicy::class);

// Single-resource routes
$router->get(   '/examples/(?P<id>\d+)',  'ExampleController@show',    ExamplePolicy::class);
$router->put(   '/examples/(?P<id>\d+)',  'ExampleController@update',  ExamplePolicy::class);
$router->delete('/examples/(?P<id>\d+)',  'ExampleController@destroy', ExamplePolicy::class);
