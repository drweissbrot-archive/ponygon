<?php

Route::post('/player', 'PlayerController@create');

Route::middleware('auth')->group(function () {
	Route::get('/lobby/{lobby}', 'LobbyController@read');

	Route::post('/lobby', 'LobbyController@create');
	Route::post('/lobby/{lobby}', 'LobbyController@join');

	Route::patch('/lobby/{lobby}/game-config', 'LobbyController@gameConfig');
	Route::post('/lobby/{lobby}/kick', 'LobbyController@kick');
	Route::post('/lobby/{lobby}/promote-to-leader', 'LobbyController@promoteToLeader');

	Route::put('/ready', ReadyController::class);

	Route::post('/match/{match}/move', 'MatchController@move');
	Route::post('/match/{match}/rematch', 'MatchController@rematch');
	Route::post('/match/{match}/end', 'MatchController@end');
});
