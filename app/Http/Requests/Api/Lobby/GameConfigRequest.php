<?php

namespace App\Http\Requests\Api\Lobby;

class GameConfigRequest extends LeaderRequest
{
	protected const RULES = [
		'selected_game' => 'string|in:tictactoe,werewolves',
		'werewolves.amor' => 'boolean',
	];

	public function rules() : array
	{
		$rules = [];

		foreach (static::RULES as $key => $validation) {
			$rules[preg_replace('/\./u', '\\.', $key)] = "nullable|{$validation}";
		}

		return $rules;
	}
}
