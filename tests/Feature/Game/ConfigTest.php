<?php

namespace Tests\Feature\Game;

use App\Games\Config;
use App\Models\Game;
use App\Models\Lobby;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_returns_the_config()
	{
		$game = Game::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertInstanceOf(Config::class, $game->config());
		$this->assertSame($game->config(), $game->instance('config'));
		$this->assertSame($game->config(), $game->instance->get('config'));
	}

	public function test_it_returns_all()
	{
		$game = Game::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals(Lobby::DEFAULT_CONFIG['tictactoe'], $game->config()->all());

		$game->config()->set('turn', 'lorem');

		$this->assertEquals(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
			'turn' => 'lorem',
		]), $game->config()->all());
	}

	public function test_it_can_be_initialized()
	{
		$game = Game::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals(Lobby::DEFAULT_CONFIG['tictactoe'], $game->config()->all());

		$this->assertSame($game->config(), $game->config()->initialize([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		]));

		$this->assertNotEquals(Lobby::DEFAULT_CONFIG['tictactoe'], $game->config()->all());

		$this->assertEquals([
			'lorem' => 'ipsum', 'nested' => ['key' => 'value'],
		], $game->config()->all());

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'config' => json_encode(['lorem' => 'ipsum', 'nested' => ['key' => 'value']]),
		]);
	}

	public function test_it_gets_values()
	{
		$game = Game::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals(['min' => 2, 'max' => 2], $game->config('playerCount'));
		$this->assertEquals(2, $game->config('playerCount.min'));
		$this->assertEquals(2, $game->config('playerCount.max'));

		$this->assertEquals(['min' => 2, 'max' => 2], $game->config()->get('playerCount'));
		$this->assertEquals(2, $game->config()->get('playerCount.min'));
		$this->assertEquals(2, $game->config()->get('playerCount.max'));

		$this->assertNull($game->config('invalid'));
		$this->assertNull($game->config()->get('invalid'));
	}

	public function test_it_sets_values()
	{
		$game = Game::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertEquals($game->config(), $game->config()->set('playerCount.min', 3));
		$this->assertEquals(3, $game->config('playerCount.min'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
				'playerCount' => ['min' => 3, 'max' => 2],
			])),
		]);

		$this->assertEquals($game->config(), $game->config()->set('playerCount', ['min' => 1, 'max' => 1]));
		$this->assertEquals(['min' => 1, 'max' => 1], $game->config('playerCount'));
		$this->assertEquals(1, $game->config('playerCount.min'));
		$this->assertEquals(1, $game->config('playerCount.max'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
				'playerCount' => ['min' => 1, 'max' => 1],
			])),
		]);

		$this->assertEquals($game->config(), $game->config()->set('new_key', 'some value'));
		$this->assertEquals('some value', $game->config('new_key'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], [
				'playerCount' => ['min' => 1, 'max' => 1],
				'new_key' => 'some value',
			])),
		]);
	}

	public function test_it_removes_values()
	{
		$game = Game::factory()->create(['game' => 'tictactoe', 'config' => Lobby::DEFAULT_CONFIG['tictactoe']]);

		$this->assertTrue($game->config()->has('playerCount'));
		$this->assertTrue($game->config()->has('playerCount.min'));
		$this->assertTrue($game->config()->has('playerCount.max'));

		$this->assertEquals($game->config(), $game->config()->remove('playerCount.min'));

		$this->assertFalse($game->config()->has('playerCount.min'));
		$this->assertNull($game->config('playerCount.min'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'config' => json_encode(array_merge(Lobby::DEFAULT_CONFIG['tictactoe'], ['playerCount' => ['max' => 2]])),
		]);

		$this->assertEquals($game->config(), $game->config()->remove('playerCount'));

		$this->assertFalse($game->config()->has('playerCount'));
		$this->assertFalse($game->config()->has('playerCount.min'));
		$this->assertFalse($game->config()->has('playerCount.max'));

		$this->assertNull($game->config('playerCount'));
		$this->assertNull($game->config('playerCount.min'));
		$this->assertNull($game->config('playerCount.max'));

		$this->assertDatabaseHas('games', [
			'id' => $game->id,
			'config' => json_encode([]),
		]);
	}
}
