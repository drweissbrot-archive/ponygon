<?php

namespace Tests\Feature\Games;

use App\Games\Instance;
use App\Games\TicTacToe\Instance as TicTacToeInstance;
use App\Models\Lobby;
use App\Models\Match;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstanceTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_returns_the_instance()
	{
		$match = Match::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertInstanceOf(TicTacToeInstance::class, $match->instance());
		$this->assertInstanceOf(Instance::class, $match->instance);
		$this->assertSame($match->instance(), $match->instance);
	}
}
