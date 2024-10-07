<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

// Route::group([
//     'prefix' => 'user',
//     'middleware' => ['auth','can:edit article'],
//     ], function () {
//     Route::get('/', 'UserController@index')->name('users.list'); //user list
//     Route::get('/add-new-user', 'UserController@create')->name('user.create');//create new one form
//     Route::post('/user-store', 'UserController@store')->name('user.store'); //store new user
//     Route::get('/edit/{id}', 'UserController@edit')->name('user.edit');
//     Route::put('/update/{id}', 'UserController@update')->name('user.update');
//     Route::get('/delete/{id}', 'UserController@destroy')->name('user.delete'); //delete
//     // Route::post('/add-permission', 'UserController@index')->name('user.addPermission');
//     Route::get('/edit-permission/{id}', 'UserController@edit_permission')->name('user.edit_permission'); //edit permission (add or delete)
//     Route::post('/update-permission/{id}', 'UserController@update_permission')->name('user.update.permission'); //edit permission (add or delete)
// });
