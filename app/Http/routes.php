<?php

//use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', ['uses' => 'SearchController@statusCheck']);
Route::get('/config', ['uses' => 'InsertController@getConfig']);

//Route::get('/mine', ['uses' => 'SearchController@searchMine']);
//Route::get('/search/{termId}', ['uses' => 'InsertController@insertTerm']);
//Route::get('/test', ['uses' => 'InsertController@insertTerms']);
//Route::get('/open', ['uses' => 'NotifyController@checkOpen']);
