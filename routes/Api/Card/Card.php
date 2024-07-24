<?php

/**
 * {{url}}/api/v1/card
 * {{url}}/api/v1/card/no-auth
 */
Route::group(['prefix' => 'card'], function (){
    Route::group(['middleware' => ['auth.jwt']], function (){
        Route::get('/', 'CardController@getListing');
        Route::get('/detail/{id}', 'CardController@getDetail');
        Route::post('/store', 'CardController@store');
        Route::post('/update', 'CardController@update');
        Route::get('/delete/{id}', 'CardController@delete');
        Route::post('/change-status', 'CardController@changeStatus');
        Route::post('/change-status-proccess', 'CardController@changeStatusProccess');
    });

});
