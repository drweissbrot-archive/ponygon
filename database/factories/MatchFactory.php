<?php

namespace Database\Factories;

use App\Models\Lobby;
use App\Models\Match;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchFactory extends Factory
{
	protected $model = Match::class;

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
