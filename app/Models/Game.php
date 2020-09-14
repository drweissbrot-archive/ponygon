<?php

namespace App\Models;

use App\Games;
use App\Support\Models\CastsToResource;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
	use CastsToResource, HasFactory;

	public const GAME_INSTANCES = [
		'tictactoe' => Games\TicTacToe\TicTacToe::class,
	];

	protected $guarded = [];

	protected $casts = [
		'config' => 'array',
		'state' => 'array',
	];

	protected $attributes = [
		'state' => '{}',
	];

	public function lobby()
	{
		return $this->belongsTo(Lobby::class);
	}
}
