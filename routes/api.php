<?php

Route::post('/player', 'PlayerController@create')
	->middleware('guest');

Route::middleware('auth')->group(function () {
	Route::post('/lobby', 'LobbyController@create');
	Route::post('/lobby/{lobby}', 'LobbyController@join');

	Route::patch('/lobby/{lobby}/game-config', 'LobbyController@gameConfig');
	Route::post('/lobby/{lobby}/kick', 'LobbyController@kick');
	Route::post('/lobby/{lobby}/promote-to-leader', 'LobbyController@promoteToLeader');

	Route::put('/ready', ReadyController::class);
});
