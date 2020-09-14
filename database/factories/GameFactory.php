<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Lobby;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
	protected $model = Game::class;

	public function definition()
	{
		return [
			'lobby_id' => Lobby::factory(),
			'game' => 'invalid',
			'config' => '{}',
			'state' => '{}',
		];
	}
}
