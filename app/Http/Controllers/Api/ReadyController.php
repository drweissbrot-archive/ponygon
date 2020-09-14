<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Response;

class ReadyController extends Controller
{
	public function __invoke(Request $request)
	{
		abort_unless($request->user()->lobby_id, 403);

		$this->validate($request, [
			'ready' => 'required|boolean',
		]);

		$request->user()->update($request->only('ready'));

		return Response::noContent();
	}
}
