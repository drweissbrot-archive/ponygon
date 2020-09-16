<?php

namespace Tests\Feature\Games;

use App\Games\State;
use App\Models\Match;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_returns_the_state()
	{
		$match = Match::factory()->create(['game' => 'tictactoe']);

		$this->assertInstanceOf(State::class, $match->state());
		$this->assertSame($match->state(), $match->instance('state'));
		$this->assertSame($match->state(), $match->instance->get('state'));
	}

	public function test_it_returns_all()
	{
		$match = Match::factory()->create([
			'game' => 'tictactoe', 'state' => ['players' => ['a', 'b'], 'turn' => 'userid'],
		]);

		$this->assertEquals(['players' => ['a', 'b'], 'turn' => 'userid'], $match->state()->all());

		$match->state()->set('turn', 'lorem');

		$this->assertEquals(['players' => ['a', 'b'], 'turn' => 'lorem'], $match->state()->all());
	}

	public function test_it_can_be_initialized()
	{
		$match = Match::factory()->create(['game' => 'tictactoe']);

		$this->assertEquals([], $match->state()->all());

		$this->assertSame($match->state(), $match->state()->initialize([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		]));

		$this->assertEquals([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		], $match->state()->all());

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'state' => json_encode(['lorem' => 'ipsum', 'nested' => ['key' => 'value']]),
		]);
	}

	public function test_it_gets_values()
	{
		$match = Match::factory()->create([
			'game' => 'tictactoe', 'state' => ['players' => ['a', 'b'], 'turn' => 'userid'],
		]);

		$this->assertEquals('userid', $match->state('turn'));

		$this->assertEquals(['a', 'b'], $match->state('players'));
		$this->assertEquals('a', $match->state('players.0'));
		$this->assertEquals('b', $match->state('players.1'));

		$this->assertNull($match->state('invalid'));
		$this->assertNull($match->state()->get('invalid'));
	}

	public function test_it_sets_values()
	{
		$match = Match::factory()->create(['game' => 'tictactoe']);

		$this->assertEquals($match->state(), $match->state()->set('some.nested.key', 'lorem'));
		$this->assertEquals(['nested' => ['key' => 'lorem']], $match->state('some'));
		$this->assertEquals('lorem', $match->state('some.nested.key'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'state' => json_encode(['some' => ['nested' => ['key' => 'lorem']]]),
		]);

		$this->assertEquals($match->state(), $match->state()->set('new_key', 'some value'));
		$this->assertEquals('some value', $match->state('new_key'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'state' => json_encode([
				'some' => ['nested' => ['key' => 'lorem']],
				'new_key' => 'some value',
			]),
		]);
	}

	public function test_it_removes_values()
	{
		$match = Match::factory()->create([
			'game' => 'tictactoe', 'state' => ['players' => ['a', 'b'], 'turn' => 'userid'],
		]);

		$this->assertTrue($match->state()->has('players'));
		$this->assertTrue($match->state()->has('players.0'));
		$this->assertTrue($match->state()->has('players.1'));

		$this->assertEquals($match->state(), $match->state()->remove('players.0'));

		$this->assertFalse($match->state()->has('playerCount.0'));
		$this->assertNull($match->state('playerCount.0'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'state' => json_encode(['players' => [1 => 'b'], 'turn' => 'userid']),
		]);

		$this->assertEquals($match->state(), $match->state()->remove('players'));

		$this->assertFalse($match->state()->has('players'));
		$this->assertFalse($match->state()->has('players.0'));
		$this->assertFalse($match->state()->has('players.1'));

		$this->assertNull($match->state('players'));
		$this->assertNull($match->state('players.0'));
		$this->assertNull($match->state('players.1'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'state' => json_encode(['turn' => 'userid']),
		]);
	}
}
