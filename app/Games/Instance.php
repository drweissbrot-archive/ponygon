<?php

namespace App\Games;

use App\Events\Player\MatchData;
use App\Models\Match;
use App\Models\Player;
use Exception;
use Illuminate\Http\Request;

abstract class Instance
{
	public $match;

	protected $cache = [];

	public function __construct(Match $match)
	{
		$this->match = $match;
	}

	abstract public function init();

	/**
	 * Makes a move based on the provided params.
	 *
	 * @return bool whether or not all players should receive updated match data
	 */
	abstract public function makeMove(Player $player, array $params) : bool;

	abstract public function dataForPlayer(Player $player) : array;

	public function authorizeMoveRequest(Request $request) : bool
	{
		$this->match->loadMissing('lobby.members');

		return $this->match->lobby->members->contains($request->user());
	}

	public function authorizeRematch(Request $request) : bool
	{
		return false;
	}

	public function initiateRematch() : bool
	{
		return true;
	}

	public function validateMoveRequest(Request $request) : array
	{
		return $request->validate($this->getMoveRequestRules($request));
	}

	public function getMoveRequestRules(Request $request) : array
	{
		throw new Exception('No validation rules provided for this game.');
	}

	public function sendMatchDataToPlayers() : void
	{
		foreach ($this->match->lobby->members as $player) {
			$this->sendMatchDataTo($player);
		}
	}

	public function sendMatchDataTo(Player $player) : void
	{
		MatchData::dispatch($player, $this->dataForPlayer($player));
	}

	public function config(...$args)
	{
		if (! array_key_exists('config', $this->cache)) {
			$this->cache['config'] = new Config($this);
		}

		return (empty($args))
			? $this->cache['config']
			: $this->cache['config']->get(...$args);
	}

	public function state(...$args)
	{
		if (! array_key_exists('state', $this->cache)) {
			$this->cache['state'] = new State($this);
		}

		return (empty($args))
			? $this->cache['state']
			: $this->cache['state']->get(...$args);
	}

	public function get($item)
	{
		switch ($item) {
			case 'config':
			return $this->config();

			case 'state':
			return $this->state();

			default:
			throw new Exception("[{$item}] does not exist on this instance");
		}
	}
}
