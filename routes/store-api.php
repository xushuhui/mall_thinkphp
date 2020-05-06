<?php

use Illuminate\Support\Facades\Route;

/**
 * 商家API
 */
Route::group(['namespace' => 'Store'], function () {
    Route::any('/getCode', 'AuthController@getCode');
    Route::any('/login', 'AuthController@login');
    Route::any('/register', 'AuthController@register');
    Route::any('/logout', 'AuthController@logout');

    Route::any('/coupon/publish', 'CouponController@publish');
    Route::any('/store', 'StoreController@update');
});