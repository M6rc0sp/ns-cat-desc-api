<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    // Rota pública de instalação do app Nuvemshop
    $router->get('/ns/install', 'NuvemshopController@install');

    $router->group(['prefix' => 'descriptions'], function () use ($router) {
        $router->get('/', 'DescriptionController@index');
        $router->get('/categories', 'DescriptionController@getCategories');
        $router->post('/', 'DescriptionController@store');
        $router->get('/category/{categoryId}', 'DescriptionController@getByCategory');
        $router->get('/{id}', 'DescriptionController@show');
        $router->put('/{id}', 'DescriptionController@update');
        $router->delete('/{id}', 'DescriptionController@destroy');
    });
});

// Rotas públicas para consumir descrições (para frontend ou widgets)
$router->group(['prefix' => 'public'], function () use ($router) {
    $router->get('/descriptions/{categoryId}', 'DescriptionController@getCategoryDescription');
    $router->get('/descriptions', 'DescriptionController@getCategoriesDescriptions');
});
