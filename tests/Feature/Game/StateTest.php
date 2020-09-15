<?php

namespace Tests\Feature\Game;

use App\Games\State;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_returns_the_state()
	{
		$game = Game::factory()->create(['game' => 'tictactoe']);

		$this->assertInstanceOf(State::class, $game->state());
		$this->assertSame($game->state(), $game->instance('state'));
		$this->assertSame($game->state(), $game->instance->get('state'));
	}

	public function test_it_returns_all()
	{
		$game = Game::factory()->create([
			'game' => 'tictactoe', 'state' => ['players' => ['a', 'b'], 'turn' => 'userid'],
		]);

		$this->assertEquals(['players' => ['a', 'b'], 'turn' => 'userid'], $game->state()->all());

		$game->state()->set('turn', 'lorem');

		$this->assertEquals(['players' => ['a', 'b'], 'turn' => 'lorem'], $game->state()->all());
	}

	public function test_it_can_be_initialized()
	{
		$game = Game::factory()->create(['game' => 'tictactoe']);

		$this->assertEquals([], $game->state()->all());

		$this->assertSame($game->state(), $game->state()->initialize([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		]));

		$this->assertEquals([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		], $game->state()->all());

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'state' => json_encode(['lorem' => 'ipsum', 'nested' => ['key' => 'value']]),
		]);
	}

	public function test_it_gets_values()
	{
		$game = Game::factory()->create([
			'game' => 'tictactoe', 'state' => ['players' => ['a', 'b'], 'turn' => 'userid'],
		]);

		$this->assertEquals('userid', $game->state('turn'));

		$this->assertEquals(['a', 'b'], $game->state('players'));
		$this->assertEquals('a', $game->state('players.0'));
		$this->assertEquals('b', $game->state('players.1'));

		$this->assertNull($game->state('invalid'));
		$this->assertNull($game->state()->get('invalid'));
	}

	public function test_it_sets_values()
	{
		$game = Game::factory()->create(['game' => 'tictactoe']);

		$this->assertEquals($game->state(), $game->state()->set('some.nested.key', 'lorem'));
		$this->assertEquals(['nested' => ['key' => 'lorem']], $game->state('some'));
		$this->assertEquals('lorem', $game->state('some.nested.key'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'state' => json_encode(['some' => ['nested' => ['key' => 'lorem']]]),
		]);

		$this->assertEquals($game->state(), $game->state()->set('new_key', 'some value'));
		$this->assertEquals('some value', $game->state('new_key'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'state' => json_encode([
				'some' => ['nested' => ['key' => 'lorem']],
				'new_key' => 'some value',
			]),
		]);
	}

	public function test_it_removes_values()
	{
		$game = Game::factory()->create([
			'game' => 'tictactoe', 'state' => ['players' => ['a', 'b'], 'turn' => 'userid'],
		]);

		$this->assertTrue($game->state()->has('players'));
		$this->assertTrue($game->state()->has('players.0'));
		$this->assertTrue($game->state()->has('players.1'));

		$this->assertEquals($game->state(), $game->state()->remove('players.0'));

		$this->assertFalse($game->state()->has('playerCount.0'));
		$this->assertNull($game->state('playerCount.0'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'state' => json_encode(['players' => [1 => 'b'], 'turn' => 'userid']),
		]);

		$this->assertEquals($game->state(), $game->state()->remove('players'));

		$this->assertFalse($game->state()->has('players'));
		$this->assertFalse($game->state()->has('players.0'));
		$this->assertFalse($game->state()->has('players.1'));

		$this->assertNull($game->state('players'));
		$this->assertNull($game->state('players.0'));
		$this->assertNull($game->state('players.1'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'state' => json_encode(['turn' => 'userid']),
		]);
	}
}
