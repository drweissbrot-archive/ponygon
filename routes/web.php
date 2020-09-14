<?php

Route::view('/', 'index');
Route::view('/play', 'play');

Route::post('/logout', LogoutController::class);

Route::get('/{lobby}', JoinLobbyController::class);
