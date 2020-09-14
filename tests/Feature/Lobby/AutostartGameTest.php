<?php

namespace Tests\Feature\Lobby;

use App\Events\Lobby\GameCancelled;
use App\Events\Lobby\GameWillStart;
use App\Jobs\Lobby\StartGame;
use App\Jobs\QueuedJob;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Queue;
use Tests\TestCase;

class AutostartGameTest extends TestCase
{
	use RefreshDatabase;

	public function test_game_starts_automatically_when_everyone_is_ready_and_a_game_is_selected()
	{
		Event::fake([GameWillStart::class]);
		Queue::fake();

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);
		Event::assertDispatched(GameWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Queue::assertPushed(StartGame::class, 1);
		Queue::assertPushed(StartGame::class, function ($job) use ($lobby) {
			return $job instanceof QueuedJob
				&& $job->lobby->id === $lobby->id
				&& $job->lobby->game->id === $lobby->refresh()->game_id
				&& $job->delay === 5;
		});
	}

	public function test_it_only_autostarts_when_the_player_count_requirements_are_met()
	{
		Event::fake([GameWillStart::class, GameCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$lobby->leader->update(['ready' => true]);
		Event::assertDispatchedTimes(GameWillStart::class, 0);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);
		Event::assertDispatched(GameWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Event::assertDispatchedTimes(GameCancelled::class, 0);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(GameCancelled::class, 1);
	}

	public function test_game_autostart_is_cancelled_when_a_player_unreadies()
	{
		Event::fake([GameCancelled::class, GameWillStart::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);
		Event::assertDispatchedTimes(GameCancelled::class, 0);
		Event::assertDispatched(GameWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$player->update(['ready' => false]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);
		Event::assertDispatchedTimes(GameCancelled::class, 1);
		Event::assertDispatched(GameCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$lobby->refresh();

		$this->assertNull($lobby->game_id);
		$this->assertNull($lobby->game);
		$this->assertCount(1, $lobby->games);

		// TODO actually test that the game is really not started
	}

	public function test_game_autostart_is_cancelled_when_a_player_joins_the_lobby()
	{
		Event::fake([GameCancelled::class, GameWillStart::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);
		Event::assertDispatchedTimes(GameCancelled::class, 0);
		Event::assertDispatched(GameWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$playerTwo = Player::factory()->create(['lobby_id' => $lobby->id]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);
		Event::assertDispatchedTimes(GameCancelled::class, 1);
		Event::assertDispatched(GameCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$lobby->refresh();

		$this->assertNull($lobby->game_id);
		$this->assertNull($lobby->game);
		$this->assertCount(1, $lobby->games);

		// TODO actually test that the game is really not started
	}

	public function test_game_autostart_ignores_leaving_players()
	{
		Event::fake([GameCancelled::class, GameWillStart::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']),
		]);

		$players = Player::factory()->count(2)->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);
		Event::assertDispatchedTimes(GameCancelled::class, 0);
		Event::assertDispatched(GameWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$players[1]->update(['lobby_id' => null]);

		Event::assertDispatchedTimes(GameWillStart::class, 1);

		$lobby->refresh();

		$this->assertNotNull($lobby->game_id);
		$this->assertNotNull($lobby->game);
		$this->assertCount(1, $lobby->games);
	}
}
