<?php

namespace App\Http\Requests\Api\Lobby;

class PromoteToLeaderRequest extends LeaderPlayerRequest
{
	public function messages() : array
	{
		return [
			'not_in' => 'The lobby leader cannot be promoted to lobby leader.',
		];
	}
}
