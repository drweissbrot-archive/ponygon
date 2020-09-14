<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Response;

class LogoutController extends Controller
{
	public function __invoke(Request $request)
	{
		$request->user()->delete();

		Auth::logout();

		return Response::noContent();
	}
}
