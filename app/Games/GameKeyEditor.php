<?php

namespace App\Games;

use Arr;

abstract class GameKeyEditor
{
	protected const KEY = null;

	protected $instance;

	public function __construct(Instance $instance)
	{
		$this->instance = $instance;
	}

	public function all() : array
	{
		return $this->instance->match->{static::KEY};
	}

	public function initialize(array $arr) : self
	{
		$this->instance->match->update([static::KEY => $arr]);

		return $this;
	}

	public function has($key) : bool
	{
		return Arr::has($this->instance->match->{static::KEY}, $key);
	}

	public function get($key)
	{
		return Arr::get($this->instance->match->{static::KEY}, $key);
	}

	public function set($key, $value) : self
	{
		$arr = $this->instance->match->{static::KEY};
		Arr::set($arr, $key, $value);

		$this->instance->match->update([static::KEY => $arr]);

		return $this;
	}

	public function remove($key) : self
	{
		$arr = $this->instance->match->{static::KEY};
		Arr::forget($arr, $key);

		$this->instance->match->update([static::KEY => $arr]);

		return $this;
	}
}
