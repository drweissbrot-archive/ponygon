<?php

namespace App\Models;

use App\Observers\LobbyObserver;
use App\Support\Models\CastsToResource;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lobby extends Model
{
	use CastsToResource, HasFactory, SoftDeletes;

	public const DEFAULT_CONFIG = [
		'selected_game' => null,

		'tictactoe' => [
			'playerCount' => ['min' => 2, 'max' => 2],
		],

		'werewolves' => [
			'playerCount' => ['min' => 2],
			'amor' => true,
		],
	];

	protected $guarded = [];

	protected $casts = [
		'game_config' => 'array',
	];

	public function __construct(array $attributes = [])
	{
		$this->bootIfNotBooted();

		$this->initializeTraits();

		$this->syncOriginal();

		$this->game_config = static::DEFAULT_CONFIG;

		$this->fill($attributes);
	}

	public static function boot() : void
	{
		parent::boot();

		static::observe(LobbyObserver::class);
	}

	public function leader()
	{
		return $this->belongsTo(Player::class);
	}

	public function members()
	{
		return $this->hasMany(Player::class);
	}

	public function game()
	{
		return $this->belongsTo(Game::class);
	}

	public function games()
	{
		return $this->hasMany(Game::class);
	}

	public function getInviteUrlAttribute() : string
	{
		return config('app.invite_url') . "/{$this->id}";
	}

	public function addMember(Player $player) : self
	{
		$this->members()->save($player);

		return $this;
	}

	/**
	 * Create a game based on the lobby's game settings and set it as the lobby's active game.
	 */
	public function createGame() : Game
	{
		$game = $this->game_config['selected_game'];

		$game = $this->games()->create([
			'lobby_id' => $this->id,
			'game' => $game,
			'config' => $this->game_config[$game],
		]);

		$this->update(['game_id' => $game->id]);

		return $game;
	}

	/**
	 * Cancels the currently active game.
	 */
	public function cancelGame() : self
	{
		if ($this->game_id) {
			$this->update(['game_id' => null]);
		}

		return $this;
	}

	public function gameCanBeStarted() : bool
	{
		if (! $this->game_config['selected_game'] || $this->invalidPlayerCount($this)) {
			return false;
		}

		$hasReady = false;
		$hasUnready = false;

		foreach ($this->members as $player) {
			if ($player->ready) {
				$hasReady = true;
			} else {
				$hasUnready = true;
			}

			if (($hasReady && $hasUnready) || ($hasUnready && $this->game)) {
				break;
			}
		}

		return $hasReady && ! $hasUnready;
	}

	protected function invalidPlayerCount() : bool
	{
		$playerCount = $this->members->count();
		$limits = $this->game_config[$this->game_config['selected_game']]['playerCount'];

		$min = $limits['min'] ?? false;
		$max = $limits['max'] ?? false;

		return ($min !== false && $playerCount < $min) || ($max !== false && $playerCount > $max);
	}
}
