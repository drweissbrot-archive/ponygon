<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LobbyResource extends JsonResource
{
	public function toArray($request)
	{
		return [
			'id' => $this->id,
			'leader_id' => $this->leader_id,
			'game_config' => $this->game_config,
			'invite_url' => $this->invite_url,
			'members' => PlayerResource::collection($this->members),
			'match' => new MatchResource($this->whenLoaded('match')),
			'match_data' => $this->when(
				$request->user() && $this->relationLoaded('match') && $this->match,
				function () use ($request) {
					return $request->user()->matchData();
				},
			),
		];
	}
}
