<?php

Route::view('/', 'index');
Route::view('/play', 'play');

Route::post('/logout', LogoutController::class)
	->middleware('auth');

Route::get('/{lobby}', JoinLobbyController::class);
