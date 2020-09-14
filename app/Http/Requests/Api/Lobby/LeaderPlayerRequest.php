<?php

namespace App\Http\Requests\Api\Lobby;

use Illuminate\Validation\Rule;

abstract class LeaderPlayerRequest extends LeaderRequest
{
	public function rules() : array
	{
		return [
			'player' => [
				'required',
				'string',
				Rule::in($this->lobby->members->pluck('id')),
				Rule::notIn($this->lobby->leader_id),
			],
		];
	}
}
