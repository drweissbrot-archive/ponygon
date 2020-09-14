<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
	public function toArray($request)
	{
		return $this->only('id', 'name', 'ready');
	}
}
