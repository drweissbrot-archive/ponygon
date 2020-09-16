<?php

namespace App\Models;

use App\Games;
use App\Games\Config;
use App\Games\Instance;
use App\Support\Models\CastsToResource;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Match extends Model
{
	use CastsToResource, HasFactory;

	public const GAME_INSTANCE_CLASSES = [
		'tictactoe' => Games\TicTacToe\Instance::class,
		'werewolves' => Games\Werewolves\Instance::class,
	];

	protected $guarded = [];

	protected $casts = [
		'config' => 'array',
		'state' => 'array',
	];

	protected $cachedInstance;

	public static function boot() : void
	{
		parent::boot();

		static::creating(function ($match) {
			if (! $match->state || $match->state === '{}') {
				$match->state = [];
			}
		});
	}

	public function resolveRouteBinding($value, $field = null)
	{
		$match = parent::resolveRouteBinding($value, $field);

		return ($match->lobby->match_id === $match->id)
			? $match
			: null;
	}

	public function lobby()
	{
		return $this->belongsTo(Lobby::class);
	}

	public function getInstanceAttribute() : Instance
	{
		return $this->instance();
	}

	public function instance(...$args)
	{
		if (! $this->cachedInstance) {
			$fqcn = static::GAME_INSTANCE_CLASSES[$this->game];
			$this->cachedInstance = new $fqcn($this);
		}

		return empty($args)
			? $this->cachedInstance
			: $this->cachedInstance->get(...$args);
	}

	public function config(...$args)
	{
		return $this->instance->config(...$args);
	}

	public function state(...$args)
	{
		return $this->instance->state(...$args);
	}
}
