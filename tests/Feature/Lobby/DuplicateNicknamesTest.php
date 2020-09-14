<?php

namespace Tests\Feature\Lobby;

use App\Models\Lobby;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateNicknamesTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_assigns_new_nicknames()
	{
		$lobby = Lobby::factory()->create();

		$first = Player::factory()->create(['name' => 'johndoe', 'lobby_id' => $lobby->id]);
		$this->assertEquals('johndoe', $first->name);

		$second = Player::factory()->create(['name' => 'johndoe', 'lobby_id' => $lobby->id]);
		$this->assertEquals('johndoe_1', $second->name);

		$third = Player::factory()->create(['name' => 'johndoe']);
		$third->update(['lobby_id' => $lobby->id]);
		$this->assertEquals('johndoe_2', $third->name);

		$fourth = Player::factory()->create(['name' => 'johndoe']);
		$fourth->update(['lobby_id' => $lobby->id]);
		$this->assertEquals('johndoe_3', $fourth->name);

		$third->delete();

		$fifth = Player::factory()->create(['name' => 'johndoe']);
		$fifth->update(['lobby_id' => $lobby->id]);
		$this->assertEquals('johndoe_4', $fifth->name);
	}

	public function test_it_sends_user_their_new_data()
	{
		$lobby = Lobby::factory()->create();
		$first = Player::factory()->create(['name' => 'johndoe', 'lobby_id' => $lobby->id]);
		$second = Player::factory()->create(['name' => 'johndoe']);

		$this->actingAs($second)
			->postJson("/api/lobby/{$lobby->id}")
			->assertOk()
			->assertSimilarJson([
				'replaced_name' => 'johndoe_1',
				'data' => [
					'id' => $lobby->id,
					'leader_id' => $lobby->leader->id,
					'game_config' => Lobby::DEFAULT_CONFIG,
					'invite_url' => $lobby->invite_url,
					'members' => [
						$lobby->leader->only('id', 'name', 'ready'),
						$first->only('id', 'name', 'ready'),
						$second->only('id', 'name', 'ready'),
					],
				],
			]);

		$this->assertEquals('johndoe', $first->name);
		$this->assertEquals('johndoe_1', $second->name);
	}
}
