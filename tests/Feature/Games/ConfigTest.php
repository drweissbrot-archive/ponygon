<?php

namespace Tests\Feature\Games;

use App\Games\Config;
use App\Models\Lobby;
use App\Models\Match;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_returns_the_config()
	{
		$match = Match::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertInstanceOf(Config::class, $match->config());
		$this->assertSame($match->config(), $match->instance('config'));
		$this->assertSame($match->config(), $match->instance->get('config'));
	}

	public function test_it_returns_all()
	{
		$match = Match::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals(Lobby::DEFAULT_CONFIG['tictactoe'], $match->config()->all());

		$match->config()->set('turn', 'lorem');

		$this->assertEquals(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
			'turn' => 'lorem',
		]), $match->config()->all());
	}

	public function test_it_can_be_initialized()
	{
		$match = Match::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals(Lobby::DEFAULT_CONFIG['tictactoe'], $match->config()->all());

		$this->assertSame($match->config(), $match->config()->initialize([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		]));

		$this->assertNotEquals(Lobby::DEFAULT_CONFIG['tictactoe'], $match->config()->all());

		$this->assertEquals([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		], $match->config()->all());

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'config' => json_encode(['lorem' => 'ipsum', 'nested' => ['key' => 'value']]),
		]);
	}

	public function test_it_gets_values()
	{
		$match = Match::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals(['min' => 2, 'max' => 2], $match->config('playerCount'));
		$this->assertEquals(2, $match->config('playerCount.min'));
		$this->assertEquals(2, $match->config('playerCount.max'));

		$this->assertEquals(['min' => 2, 'max' => 2], $match->config()->get('playerCount'));
		$this->assertEquals(2, $match->config()->get('playerCount.min'));
		$this->assertEquals(2, $match->config()->get('playerCount.max'));

		$this->assertNull($match->config('invalid'));
		$this->assertNull($match->config()->get('invalid'));
	}

	public function test_it_sets_values()
	{
		$match = Match::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals($match->config(), $match->config()->set('playerCount.min', 3));
		$this->assertEquals(3, $match->config('playerCount.min'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
				'playerCount' => ['min' => 3, 'max' => 2],
			])),
		]);

		$this->assertEquals($match->config(), $match->config()->set('playerCount', ['min' => 1, 'max' => 1]));
		$this->assertEquals(['min' => 1, 'max' => 1], $match->config('playerCount'));
		$this->assertEquals(1, $match->config('playerCount.min'));
		$this->assertEquals(1, $match->config('playerCount.max'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
				'playerCount' => ['min' => 1, 'max' => 1],
			])),
		]);

		$this->assertEquals($match->config(), $match->config()->set('new_key', 'some value'));
		$this->assertEquals('some value', $match->config('new_key'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
				'playerCount' => ['min' => 1, 'max' => 1],
				'new_key' => 'some value',
			])),
		]);
	}

	public function test_it_removes_values()
	{
		$match = Match::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertTrue($match->config()->has('playerCount'));
		$this->assertTrue($match->config()->has('playerCount.min'));
		$this->assertTrue($match->config()->has('playerCount.max'));

		$this->assertEquals($match->config(), $match->config()->remove('playerCount.min'));

		$this->assertFalse($match->config()->has('playerCount.min'));
		$this->assertNull($match->config('playerCount.min'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], ['playerCount' => ['max' => 2]])),
		]);

		$this->assertEquals($match->config(), $match->config()->remove('playerCount'));

		$this->assertFalse($match->config()->has('playerCount'));
		$this->assertFalse($match->config()->has('playerCount.min'));
		$this->assertFalse($match->config()->has('playerCount.max'));

		$this->assertNull($match->config('playerCount'));
		$this->assertNull($match->config('playerCount.min'));
		$this->assertNull($match->config('playerCount.max'));

		$this->assertDatabaseHas('matches', [
			'id' => $match->id,
			'config' => json_encode([]),
		]);
	}
}
