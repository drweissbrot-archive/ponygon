<?php

namespace Tests\Feature\Lobby;

use App\Models\Lobby;
use App\Models\Match;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Queue;
use Tests\TestCase;

class InterfaceTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_generates_invite_url()
	{
		$lobby = Lobby::factory()->create();

		$this->assertEquals("https://testing-invite-url.test/{$lobby->id}", $lobby->invite_url);
	}

	public function test_it_adds_the_lobby_leader_as_a_member()
	{
		$player = Player::factory()->create();
		$lobby = Lobby::create(['leader_id' => $player->id]);

		$player->refresh();

		$this->assertInstanceOf(Collection::class, $lobby->members);
		$this->assertCount(1, $lobby->members);
		$this->assertTrue($lobby->members->contains($player));
		$this->assertEquals($lobby->id, $player->lobby_id);
	}

	public function test_it_creates_matches()
	{
		Queue::fake(); // prevent that the match is cancelled by the StartMatch job

		// this tests checks if the match is created with the lobby's game
		// config instead of the default one, so if the default config value
		// for amor is not true (as this test expects), this test needs to
		// be updated
		$this->assertTrue(Lobby::DEFAULT_CONFIG['werewolves']['amor']);

		$config = array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']);
		$config['werewolves']['amor'] = false;

		$lobby = Lobby::factory()->create(['game_config' => $config]);

		$this->assertDatabaseCount('matches', 0);

		$match = $lobby->createMatch();

		$this->assertInstanceOf(Match::class, $match);
		$this->assertTrue($match->exists());

		$this->assertDatabaseCount('matches', 1);
		$this->assertDatabaseCount('lobbies', 1);

		$lobby->refresh();

		$this->assertEquals($lobby->id, $match->lobby_id);
		$this->assertEquals($lobby->id, $match->lobby->id);
		$this->assertEquals($match->id, $lobby->match_id);
		$this->assertEquals($match->id, $lobby->match->id);

		$this->assertCount(1, $lobby->matches);
		$this->assertTrue($lobby->matches->contains($match));

		$this->assertDatabaseHas('lobbies', [
			'match_id' => $match->id,
		]);

		$this->assertDatabaseHas('matches', [
			'lobby_id' => $lobby->id,
			'game' => 'werewolves',
			'state' => '[]',
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['werewolves'], ['amor' => false])),
		]);
	}

	public function test_it_cancels_matches()
	{
		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']),
		]);

		// test it doesn't do anything when no match is active
		$this->assertInstanceOf(Lobby::class, $lobby->cancelMatch());

		$this->assertDatabaseCount('lobbies', 1);
		$this->assertDatabaseCount('matches', 0);

		$match = $lobby->createMatch();

		$lobby->cancelMatch()->refresh();

		$this->assertNull($lobby->match_id);
		$this->assertNull($lobby->match);

		$this->assertCount(1, $lobby->matches);
		$this->assertTrue($lobby->matches->contains($match));
	}
}
