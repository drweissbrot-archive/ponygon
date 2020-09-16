<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Match;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Response;

class MatchController extends Controller
{
	public function move(Request $request, Match $match)
	{
		if (! $match->instance()->authorizeMoveRequest($request)) {
			throw new AuthorizationException;
		}

		$validated = $match->instance()->validateMoveRequest($request);

		$shouldSendData = $match->instance()->makeMove($request->user(), $validated);

		if ($shouldSendData) {
			$match->instance()->sendMatchDataToPlayers();
		}

		return Response::noContent();
	}

	public function rematch(Request $request, Match $match)
	{
		if (! $match->instance()->authorizeRematch($request)) {
			throw new AuthorizationException;
		}

		$shouldSendData = $match->instance()->initiateRematch();

		if ($shouldSendData) {
			$match->instance()->sendMatchDataToPlayers();
		}

		return Response::noContent();
	}

	public function end(Request $request, Match $match)
	{
		if (! $match->instance()->authorizeEndingMatch($request)) {
			throw new AuthorizationException;
		}

		$match->lobby->update(['match_id' => null]);

		return Response::noContent();
	}
}
