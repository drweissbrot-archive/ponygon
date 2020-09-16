<?php

namespace App\Games;

use App\Models\Match;
use App\Models\Player;
use Exception;

abstract class Instance
{
	public $match;

	protected $cache = [];

	public function __construct(Match $match)
	{
		$this->match = $match;
	}

	abstract public function init();

	abstract public function dataForPlayer(Player $player) : array;

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
