<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
	public function test_it_shows_landing_page()
	{
		$this->get('/')
			->assertOk()
			->assertViewIs('index')
			->assertSee('Welcome to Ponygon');
	}
}
