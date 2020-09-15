<?php

namespace App\Games;

use App\Models\Game;
use Exception;

abstract class Instance
{
	public $game;

	protected $cache = [];

	public function __construct(Game $game)
	{
		$this->game = $game;
	}

	abstract public function init();

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
