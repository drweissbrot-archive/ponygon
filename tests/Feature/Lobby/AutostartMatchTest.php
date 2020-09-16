<?php

namespace Tests\Feature\Lobby;

use App\Events\Lobby\MatchCancelled;
use App\Events\Lobby\MatchWillStart;
use App\Jobs\Lobby\StartMatch;
use App\Jobs\QueuedJob;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Queue;
use Tests\TestCase;

class AutostartMatchTest extends TestCase
{
	use RefreshDatabase;

	public function test_game_starts_automatically_when_everyone_is_ready_and_a_game_is_selected()
	{
		Event::fake([MatchWillStart::class]);
		Queue::fake();

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);
		Event::assertDispatched(MatchWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Queue::assertPushed(StartMatch::class, 1);
		Queue::assertPushed(StartMatch::class, function ($job) use ($lobby) {
			return $job instanceof QueuedJob
				&& $job->lobby->id === $lobby->id
				&& $job->lobby->match->id === $lobby->refresh()->match_id
				&& $job->delay === 5;
		});
	}

	public function test_it_only_autostarts_when_the_player_count_requirements_are_met()
	{
		Event::fake([MatchWillStart::class, MatchCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$lobby->leader->update(['ready' => true]);
		Event::assertDispatchedTimes(MatchWillStart::class, 0);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);
		Event::assertDispatched(MatchWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Event::assertDispatchedTimes(MatchCancelled::class, 0);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(MatchCancelled::class, 1);
	}

	public function test_game_autostart_is_cancelled_when_a_player_unreadies()
	{
		Event::fake([MatchCancelled::class, MatchWillStart::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);
		Event::assertDispatchedTimes(MatchCancelled::class, 0);
		Event::assertDispatched(MatchWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$player->update(['ready' => false]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);
		Event::assertDispatchedTimes(MatchCancelled::class, 1);
		Event::assertDispatched(MatchCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$lobby->refresh();

		$this->assertNull($lobby->match_id);
		$this->assertNull($lobby->match);
		$this->assertCount(1, $lobby->matches);

		// TODO actually test that the game is really not started
	}

	public function test_game_autostart_is_cancelled_when_a_player_joins_the_lobby()
	{
		Event::fake([MatchCancelled::class, MatchWillStart::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);
		Event::assertDispatchedTimes(MatchCancelled::class, 0);
		Event::assertDispatched(MatchWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$playerTwo = Player::factory()->create(['lobby_id' => $lobby->id]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);
		Event::assertDispatchedTimes(MatchCancelled::class, 1);
		Event::assertDispatched(MatchCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$lobby->refresh();

		$this->assertNull($lobby->match_id);
		$this->assertNull($lobby->match);
		$this->assertCount(1, $lobby->matches);

		// TODO actually test that the game is really not started
	}

	public function test_game_autostart_ignores_leaving_players()
	{
		Event::fake([MatchCancelled::class, MatchWillStart::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'werewolves']),
		]);

		$players = Player::factory()->count(2)->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 0);

		$lobby->leader->update(['ready' => true]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);
		Event::assertDispatchedTimes(MatchCancelled::class, 0);
		Event::assertDispatched(MatchWillStart::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$players[1]->update(['lobby_id' => null]);

		Event::assertDispatchedTimes(MatchWillStart::class, 1);

		$lobby->refresh();

		$this->assertNotNull($lobby->match_id);
		$this->assertNotNull($lobby->match);
		$this->assertCount(1, $lobby->matches);
	}
}
