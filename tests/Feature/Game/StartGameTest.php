<?php

namespace Tests\Feature\Game;

use App\Events\Lobby\GameCancelled;
use App\Events\Lobby\GameStarting;
use App\Jobs\Lobby\StartGame;
use App\Jobs\QueuedJob;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Queue;
use Tests\TestCase;

class StartGameTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_initializes_and_starts_the_game()
	{
		Event::fake([GameStarting::class, GameCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);
		$lobby->leader->update(['ready' => true]);

		$lobby->refresh();

		Event::assertDispatchedTimes(GameStarting::class, 1);
		Event::assertDispatched(GameStarting::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['id' => $lobby->game->id, 'game' => 'tictactoe'];
		});

		Event::assertDispatchedTimes(GameCancelled::class, 0);
	}

	public function test_it_does_not_start_the_game_when_players_are_not_ready()
	{
		Event::fake([GameStarting::class, GameCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		$lobby->createGame();

		Event::assertDispatchedTimes(GameCancelled::class, 1);
		Event::assertDispatched(GameCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Event::assertDispatchedTimes(GameStarting::class, 0);

		$lobby->refresh();

		$this->assertCount(1, $lobby->games);
		$this->assertNull($lobby->game_id);
		$this->assertNull($lobby->game);
	}

	public function test_it_does_not_start_the_game_when_player_requirements_are_not_met()
	{
		Event::fake([GameStarting::class, GameCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$lobby->leader->update(['ready' => true]);

		$lobby->createGame();

		Event::assertDispatchedTimes(GameCancelled::class, 1);
		Event::assertDispatched(GameCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Event::assertDispatchedTimes(GameStarting::class, 0);

		$lobby->refresh();

		$this->assertCount(1, $lobby->games);
		$this->assertNull($lobby->game_id);
		$this->assertNull($lobby->game);
	}

	public function test_it_does_not_start_when_the_game_is_not_the_same_anymore()
	{
		Event::fake([GameStarting::class, GameCancelled::class]);
		Queue::fake();

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Queue::assertNothingPushed();

		$lobby->leader->update(['ready' => true]);

		$firstGame = $lobby->refresh()->game;

		Queue::assertPushed(StartGame::class, 1);
		Queue::assertPushed(StartGame::class, function ($job) use ($firstGame, $lobby) {
			return $job instanceof QueuedJob
				&& $job->lobby->id === $lobby->id
				&& $job->lobby->game->id === $firstGame->id
				&& $job->delay === 5;
		});

		$lobby->createGame();

		Queue::assertPushed(StartGame::class, 2);
		Queue::assertPushed(StartGame::class, function ($job) use ($firstGame, $lobby) {
			return $job instanceof QueuedJob
				&& $job->lobby->id === $lobby->id
				&& $job->lobby->game->id === $lobby->game_id
				&& $job->lobby->game->id !== null
				&& $job->lobby->game->id !== $firstGame->id
				&& $job->delay === 5;
		});

		// run the job manually with the data the first job would have received if it hadn't been faked
		app(StartGame::class, ['lobby' => $lobby, 'game' => $firstGame])->handle();

		$this->assertCount(2, $lobby->load('games')->games);

		Event::assertDispatchedTimes(GameCancelled::class, 0);
		Event::assertDispatchedTimes(GameStarting::class, 0);
	}
}
