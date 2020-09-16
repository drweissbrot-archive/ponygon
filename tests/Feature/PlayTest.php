<?php

namespace Tests\Feature;

use App\Models\Lobby;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_works()
	{
		$this->get('/play')
			->assertOk()
			->assertViewIs('play')
			->assertSee('<script src="' . mix('js/app.js') . '" async defer></script>', false)
			->assertSeeInOrder([
				'<div id="app">',
				'<ponygon-app>',
				'Loading...',
				'</ponygon-app>',
				'<noscript>',
				'Ponygon requires JavaScript.',
				'Please enable JavaScript in your browser.',
				'</noscript>',
				'</div>',
			], false);
	}

	public function test_a_lobby_id_redirects_to_play_with_lobby_id_set()
	{
		$lobby = Lobby::factory()->create();

		$this->get("https://testing-invite-url.test/{$lobby->id}")
			->assertRedirect('https://ponygon-testing.test/play#' . $lobby->id);

		$this->get('/play#' . $lobby->id)
			->assertOk()
			->assertViewIs('play');
	}
}
