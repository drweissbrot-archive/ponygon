<?php

namespace App\Models;

use App\Observers\PlayerObserver;
use App\Support\Models\CastsToResource;
use GoldSpecDigital\LaravelEloquentUUID\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Player extends Authenticatable
{
	use CastsToResource, HasFactory, Notifiable, SoftDeletes;

	protected $guarded = [];

	protected $hidden = [
		'token',
	];

	protected $casts = [
		'ready' => 'bool',
	];

	protected $attributes = [
		'ready' => false,
	];

	public static function boot() : void
	{
		parent::boot();

		static::observe(PlayerObserver::class);
	}

	public function lobby()
	{
		return $this->belongsTo(Lobby::class);
	}

	public function matchData()
	{
		if (! $this->lobby->match) {
			$this->load('lobby.match');
		}

		return ($this->lobby->match)
			? $this->lobby->match->instance()->dataForPlayer($this)
			: null;
	}

	public function inLobby(Lobby $lobby = null) : bool
	{
		return ($lobby)
			? $lobby->exists() && $lobby->id !== null && $lobby->id === $this->lobby_id
			: $this->lobby_id !== null;
	}
}
