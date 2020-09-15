<?php

namespace Tests\Feature\Game;

use App\Games\Instance;
use App\Games\TicTacToe\Instance as TicTacToeInstance;
use App\Models\Game;
use App\Models\Lobby;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstanceTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_returns_the_instance()
	{
		$game = Game::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertInstanceOf(TicTacToeInstance::class, $game->instance());
		$this->assertInstanceOf(Instance::class, $game->instance);
		$this->assertSame($game->instance(), $game->instance);
	}
}
