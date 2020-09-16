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
			'winner' => false,
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
			'winner' => $this->state('winner'),
		];
	}

	public function authorizeRematch(Request $request) : bool
	{
		return $this->state('winner') !== false;
	}

	public function initiateRematch() : bool
	{
		$this->state()->set('board', [[null, null, null], [null, null, null], [null, null, null]]);
		$this->state()->set('winner', false);

		return true;
	}

	public function authorizeMoveRequest(Request $request) : bool
	{
		return parent::authorizeMoveRequest($request)
			&& $request->user()->id === $this->state('turn')
			&& ! $this->state('winner');
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

		$this->determineIfMatchEnded();

		return true;
	}

	protected function determineIfMatchEnded() : void
	{
		$needToBeEqual = [
			[[0, 0], [0, 1], [0, 2]],
			[[1, 0], [1, 1], [1, 2]],
			[[2, 0], [2, 1], [2, 2]],

			[[0, 0], [1, 0], [2, 0]],
			[[0, 1], [1, 1], [2, 1]],
			[[0, 2], [1, 2], [2, 2]],

			[[0, 0], [1, 1], [2, 2]],
			[[0, 2], [1, 1], [2, 0]],
		];

		foreach ($needToBeEqual as [[$ax, $ay], [$bx, $by], [$cx, $cy]]) {
			if (($winner = $this->state("board.{$ax}.{$ay}"))
				&& $this->state("board.{$ax}.{$ay}") === $this->state("board.{$bx}.{$by}")
				&& $this->state("board.{$ax}.{$ay}") === $this->state("board.{$cx}.{$cy}")
			) {
				$this->state()->set("score.{$winner}", $this->state()->get("score.{$winner}") + 1);
				$this->state()->set('winner', $this->state($winner));

				return;
			}
		}

		$this->determineIfTied();
	}

	protected function determineIfTied() : void
	{
		foreach ($this->state('board') as $nodes) {
			foreach ($nodes as $node) {
				if ($node === null) {
					return;
				}
			}
		}

		$this->state()->set('winner', 'tie');
	}
}
