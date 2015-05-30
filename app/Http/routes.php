<?php

Route::get('/config', ['uses' => 'InsertController@getConfig']);
Route::post('/sms', ['uses' => 'SmsResponseController@postSms']);
