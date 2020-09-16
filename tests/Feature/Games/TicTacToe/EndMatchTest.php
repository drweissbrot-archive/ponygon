<?php

namespace Tests\Feature\Games\TicTacToe;

use App\Events\Lobby\MatchCancelled;
use App\Events\Lobby\MatchEnded;
use App\Models\Lobby;
use App\Models\Player;
use Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndMatchTest extends TestCase
{
	use RefreshDatabase;

	public function test_lobby_leader_can_end_match()
	{
		Event::fake([MatchCancelled::class, MatchEnded::class]);

		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->match->state()->set('winner', 'tie');

		$this->actingAs($lobby->leader)
			->postJson("/api/match/{$lobby->match->id}/end")
			->assertNoContent();

		Event::assertDispatched(MatchCancelled::class, 1);
		Event::assertDispatched(MatchCancelled::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		Event::assertDispatched(MatchEnded::class, 1);
		Event::assertDispatched(MatchEnded::class, function ($event) use ($lobby) {
			return $event instanceof ShouldBroadcastNow
				&& $event->broadcastOn()->name === "private-lobby.{$lobby->id}";
		});

		$lobby->refresh();

		$this->assertCount(1, $lobby->matches);
		$this->assertNull($lobby->match_id);
		$this->assertNull($lobby->match);

		$this->assertTrue($lobby->members->every(fn ($player) => $player->ready === false));
	}

	public function test_inactive_matches_cannot_be_ended()
	{
		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->update(['match_id' => null]);

		Event::fake([MatchCancelled::class, MatchEnded::class]);

		$this->actingAs($lobby->leader)
			->postJson("/api/match/{$lobby->match->id}/end")
			->assertNotFound();

		Event::assertDispatched(MatchCancelled::class, 0);
		Event::assertDispatched(MatchEnded::class, 0);

		$this->assertTrue($lobby->members->every(fn ($player) => $player->ready === true));
	}

	public function test_only_lobby_leader_can_end_match()
	{
		Event::fake([MatchCancelled::class, MatchEnded::class]);

		[$lobby, $player] = $this->createLobbyWithGame();
		$lobby->match->state()->set('winner', 'tie');

		$this->postJson("/api/match/{$lobby->match->id}/end")
			->assertUnauthorized();

		$this->actingAs(Player::factory()->create())
			->postJson("/api/match/{$lobby->match->id}/end")
			->assertForbidden();

		$this->actingAs($player)
			->postJson("/api/match/{$lobby->match->id}/end")
			->assertForbidden();

		Event::assertDispatched(MatchCancelled::class, 0);
		Event::assertDispatched(MatchEnded::class, 0);

		$lobby->refresh();

		$this->assertCount(1, $lobby->matches);
		$this->assertNotNull($lobby->match_id);
		$this->assertNotNull($lobby->match);

		$this->assertTrue($lobby->members->every(fn ($player) => $player->ready === true));
	}

	protected function createLobbyWithGame() : array
	{
		$lobby = Lobby::factory()->create([
			'game_config' => array_merge(Lobby::DEFAULT_CONFIG, ['selected_game' => 'tictactoe']),
		]);

		$player = Player::factory()->create(['lobby_id' => $lobby->id, 'ready' => true]);
		$lobby->leader->update(['ready' => true]);

		$lobby->refresh();

		$lobby->match->state()->set('turn', $player->id);
		$lobby->match->state()->set('x', $player->id);
		$lobby->match->state()->set('o', $lobby->leader->id);

		return [$lobby, $player];
	}
}
