<?php

use App\Http\Controllers\SmsResponseController;

Route::get('/config', ['uses' => 'InsertController@getConfig']);
Route::post('/sms', ['uses' => 'SmsResponseController@postSms']);

Route::get('/', function()
{

   $controller = new SmsResponseController();
   return $controller->parseBody('+17146550347', 'terms');

});
