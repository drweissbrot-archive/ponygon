<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
	public function toArray($request)
	{
		return [
			'id' => $this->id,
			'game' => $this->game,
		];
	}
}
