<?php

namespace Tests\Feature\Player;

use App\Models\Player;
use Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PlayerTest extends TestCase
{
	use RefreshDatabase, WithFaker;

	public function test_one_can_create_a_player()
	{
		Player::factory()->create();
		$this->assertDatabaseCount('players', 1);

		$this->travel(1)->seconds();

		$res = $this->postJson('/api/player', [
			'name' => $this->faker->username,
		])
			->assertCreated();

		$this->assertDatabaseCount('players', 2);
		$player = Player::latest()->first();

		$res->assertExactJson([
			'data' => [
				'id' => $player->id,
				'name' => $player->name,
				'ready' => false,
			],
		]);

		$this->assertAuthenticatedAs($player);
		$this->assertNotNull($player->remember_token);
	}

	public function test_names_must_be_between_3_and_32_characters()
	{
		$this->postJson('/api/player')
			->assertStatus(422)
			->assertJsonValidationErrors([
				'name' => 'The name field is required.',
			]);

		$this->assertDatabaseCount('players', 0);

		$this->postJson('/api/player', [
			'name' => '12',
		])
			->assertStatus(422)
			->assertJsonValidationErrors([
				'name' => 'The name must be between 3 and 32 characters.',
			]);

		$this->assertDatabaseCount('players', 0);

		$this->postJson('/api/player', [
			'name' => '123456789 123456789 123465789 123',
		])
			->assertStatus(422)
			->assertJsonValidationErrors([
				'name' => 'The name must be between 3 and 32 characters.',
			]);

		$this->assertDatabaseCount('players', 0);

		$this->postJson('/api/player', [
			'name' => '123',
		])
			->assertCreated();
		$this->assertDatabaseCount('players', 1);

		Auth::logout();

		$this->postJson('/api/player', [
			'name' => '123456789 123456789 123465789 12',
		])
			->assertCreated();
		$this->assertDatabaseCount('players', 2);
	}

	public function test_authenticated_players_cannot_create_players()
	{
		$this->actingAs(Player::factory()->create())
			->postJson('/api/player', [
				'name' => $this->faker->username,
			])->assertForbidden();
	}
}
