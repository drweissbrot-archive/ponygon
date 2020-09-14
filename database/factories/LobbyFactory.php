<?php

namespace Database\Factories;

use App\Models\Lobby;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

class LobbyFactory extends Factory
{
	protected $model = Lobby::class;

	public function definition()
	{
		return [
			'leader_id' => Player::factory(),
		];
	}
}
