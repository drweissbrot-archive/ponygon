<?php

namespace Tests;

use Arr;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\Assert as PHPUnit;

abstract class TestCase extends BaseTestCase
{
	use CreatesApplication;

	protected function assertSimilar(array $expected, array $actual) : self
	{
		PHPUnit::assertEquals(
			json_encode(Arr::sortRecursive($expected)),
			json_encode(Arr::sortRecursive($actual)),
		);

		return $this;
	}
}
