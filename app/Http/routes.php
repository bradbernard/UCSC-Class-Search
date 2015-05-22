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
Route::get('/mine', ['uses' => 'SearchController@searchMine']);

//Route::get('/', function()
//{
//
//   $searchController = new SearchController();
//
//   $class = [
//
//      'name'            => 'CMPS 280S - 01',
//      'teacherFull'     => 'Long,D.D.',
//      'time'            => '01:00PM-03:00PM',
//      'days'            => 'M',
//      'type'            => 'SEM',
//      'location'        => 'Engineer 2 599',
//      'credits'         => '2',
//      'teacherShort'    => 'Long',
//      'subjectShort'    => 'CMPS',
//      'matches'         => '6'
//
//   ];
//
//   $number = "+17146550347";
//   $termId = 2158;
//
//   return $searchController->searchClass($number, $class, $termId);
//
//});
