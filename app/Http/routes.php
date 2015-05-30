<?php

Route::get('/', ['uses' => 'SearchController@statusCheck']);
Route::get('/config', ['uses' => 'InsertController@getConfig']);
Route::post('/sms', ['uses' => 'SmsResponseController@postSms']);
