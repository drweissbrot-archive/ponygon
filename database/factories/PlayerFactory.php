<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
	protected $model = Player::class;

	public function definition()
	{
		return [
			'name' => $this->faker->username,
			'token' => '$argon2id$v=19$m=1024,t=2,p=2$VlpZWEtkMldFMHh2VUJjaQ$37/59aK3VxN3TNNKFzD4AAUMLPhyCRfEayrPQVgF1gQ', // token
		];
	}
}
