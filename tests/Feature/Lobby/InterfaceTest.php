<?php

namespace Tests\Feature\Lobby;

use App\Models\Game;
use App\Models\Lobby;
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

	public function test_it_creates_games()
	{
		Queue::fake(); // prevent that the game is cancelled by the StartGame job

		// this tests checks if the game is created with the lobby's game config
		// instead of the default one, so if the default config value for amor
		// is not true (as this test expects), this test needs to be updated
		$this->assertTrue(Lobby::DEFAULT_CONFIG['werewolves']['amor']);

		$config = array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']);
		$config['werewolves']['amor'] = false;

		$lobby = Lobby::factory()->create(['game_config' => $config]);

		$this->assertDatabaseCount('games', 0);

		$game = $lobby->createGame();

		$this->assertInstanceOf(Game::class, $game);
		$this->assertTrue($game->exists());

		$this->assertDatabaseCount('games', 1);
		$this->assertDatabaseCount('lobbies', 1);

		$lobby->refresh();

		$this->assertEquals($lobby->id, $game->lobby_id);
		$this->assertEquals($lobby->id, $game->lobby->id);
		$this->assertEquals($game->id, $lobby->game_id);
		$this->assertEquals($game->id, $lobby->game->id);

		$this->assertCount(1, $lobby->games);
		$this->assertTrue($lobby->games->contains($game));

		$this->assertDatabaseHas('lobbies', [
			'game_id' => $game->id,
		]);

		$this->assertDatabaseHas('games', [
			'lobby_id' => $lobby->id,
			'game' => 'werewolves',
			'state' => '[]',
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['werewolves'], ['amor' => false])),
		]);
	}

	public function test_it_cancels_games()
	{
		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']),
		]);

		// test it doesn't do anything when no game is active
		$this->assertInstanceOf(Lobby::class, $lobby->cancelGame());

		$this->assertDatabaseCount('lobbies', 1);
		$this->assertDatabaseCount('games', 0);

		$game = $lobby->createGame();

		$lobby->cancelGame()->refresh();

		$this->assertNull($lobby->game_id);
		$this->assertNull($lobby->game);

		$this->assertCount(1, $lobby->games);
		$this->assertTrue($lobby->games->contains($game));
	}
}
