<?php

namespace App\Http\Requests\Api\Lobby;

use Illuminate\Foundation\Http\FormRequest;

abstract class LeaderRequest extends FormRequest
{
	public function authorize() : bool
	{
		return $this->user()->id === $this->lobby->leader_id;
	}
}
