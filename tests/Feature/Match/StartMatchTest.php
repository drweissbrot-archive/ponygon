<?php

namespace Tests\Feature\Match;

use App\Events\Lobby\MatchCancelled;
use App\Events\Lobby\MatchStarting;
use App\Jobs\Lobby\StartMatch;
use App\Jobs\QueuedJob;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Queue;
use Tests\TestCase;

class StartMatchTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_initializes_and_starts_the_match()
	{
		Event::fake([MatchStarting::class, MatchCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);
		$lobby->leader->update(['ready' => true]);

		$lobby->refresh();

		Event::assertDispatchedTimes(MatchStarting::class, 1);
		Event::assertDispatched(MatchStarting::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}"
				&& $event->broadcastWith() === ['id' => $lobby->match->id, 'game' => 'tictactoe'];
		});

		Event::assertDispatchedTimes(MatchCancelled::class, 0);
	}

	public function test_it_does_not_start_the_match_when_players_are_not_ready()
	{
		Event::fake([MatchStarting::class, MatchCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		$lobby->createMatch();

		Event::assertDispatchedTimes(MatchCancelled::class, 1);
		Event::assertDispatched(MatchCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Event::assertDispatchedTimes(MatchStarting::class, 0);

		$lobby->refresh();

		$this->assertCount(1, $lobby->matches);
		$this->assertNull($lobby->match_id);
		$this->assertNull($lobby->match);
	}

	public function test_it_does_not_start_the_match_when_player_requirements_are_not_met()
	{
		Event::fake([MatchStarting::class, MatchCancelled::class]);

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$lobby->leader->update(['ready' => true]);

		$lobby->createMatch();

		Event::assertDispatchedTimes(MatchCancelled::class, 1);
		Event::assertDispatched(MatchCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Event::assertDispatchedTimes(MatchStarting::class, 0);

		$lobby->refresh();

		$this->assertCount(1, $lobby->matches);
		$this->assertNull($lobby->match_id);
		$this->assertNull($lobby->match);
	}

	public function test_it_does_not_start_when_the_match_is_not_the_same_anymore()
	{
		Event::fake([MatchStarting::class, MatchCancelled::class]);
		Queue::fake();

		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);

		Queue::assertNothingPushed();

		$lobby->leader->update(['ready' => true]);

		$firstMatch = $lobby->refresh()->match;

		Queue::assertPushed(StartMatch::class, 1);
		Queue::assertPushed(StartMatch::class, function ($job) use ($firstMatch, $lobby) {
			return $job instanceof QueuedJob
				&& $job->lobby->id === $lobby->id
				&& $job->lobby->match->id === $firstMatch->id
				&& $job->delay === 5;
		});

		$lobby->createMatch();

		Queue::assertPushed(StartMatch::class, 2);
		Queue::assertPushed(StartMatch::class, function ($job) use ($firstMatch, $lobby) {
			return $job instanceof QueuedJob
				&& $job->lobby->id === $lobby->id
				&& $job->lobby->match->id === $lobby->match_id
				&& $job->lobby->match->id !== null
				&& $job->lobby->match->id !== $firstMatch->id
				&& $job->delay === 5;
		});

		// run the job manually with the data the first job would have received if it hadn't been faked
		app(StartMatch::class, ['lobby' => $lobby, 'match' => $firstMatch])->handle();

		$this->assertCount(2, $lobby->load('matches')->matches);

		Event::assertDispatchedTimes(MatchCancelled::class, 0);
		Event::assertDispatchedTimes(MatchStarting::class, 0);
	}
}
