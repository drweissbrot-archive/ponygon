<?php

namespace App\Http\Requests\Api\Lobby;

class KickRequest extends LeaderPlayerRequest
{
	public function messages() : array
	{
		return [
			'not_in' => 'The lobby leader cannot be kicked.',
		];
	}
}
