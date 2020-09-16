<?php

namespace App\Games\TicTacToe;

use App\Games\Instance as BaseInstance;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Instance extends BaseInstance
{
	public function init()
	{
		$players = $this->match->lobby->members->shuffle();

		$this->state()->initialize([
			'x' => $players[0]->id,
			'o' => $players[1]->id,

			'turn' => $players[0]->id,
			'board' => [[null, null, null], [null, null, null], [null, null, null]],
			'score' => ['x' => 0, 'o' => 0],
			'ended' => false,
		]);
	}

	public function dataForPlayer(Player $player) : array
	{
		return [
			'scoreboard' => [
				$this->state('x') => [
					'role' => 'X', 'score' => $this->state('score.x'),
				],
				$this->state('o') => [
					'role' => 'O', 'score' => $this->state('score.o'),
				],
			],

			'turn' => $this->state('turn'),
			'board' => $this->state('board'),
		];
	}

	public function authorizeMoveRequest(Request $request) : bool
	{
		return parent::authorizeMoveRequest($request)
			&& $request->user()->id === $this->state('turn')
			&& ! $this->state('ended');
	}

	public function validateMoveRequest(Request $request) : array
	{
		$validated = parent::validateMoveRequest($request);

		if ($this->state("board.{$request->x}.{$request->y}") !== null) {
			throw ValidationException::withMessages([
				'x' => 'The provided node is already claimed.',
			]);
		}

		return $validated;
	}

	public function getMoveRequestRules(Request $request) : array
	{
		return [
			'x' => 'required|int|between:0,2',
			'y' => 'required|int|between:0,2',
		];
	}

	public function makeMove(Player $player, array $data) : bool
	{
		$this->state()->set(
			"board.{$data['x']}.{$data['y']}",
			($this->state('x') === $player->id) ? 'x' : 'o',
		);

		$this->state()->set(
			'turn',
			$this->match->lobby->members->firstWhere('id', '!=', $player->id)->id,
		);

		return true;
	}
}
