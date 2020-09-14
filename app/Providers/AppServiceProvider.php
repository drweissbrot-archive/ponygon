<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register()
	{
		if ($this->app->isLocal()) {
			$this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
			$this->app->register(TelescopeServiceProvider::class);
		}
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot()
	{
		$macros = [
			Collection::class => [
				'recursive' => function () {
					return $this->map(function ($value) {
						if (is_object($value) || is_array($value)) {
							return collect($value)->recursive();
						}

						return $value;
					});
				},
			],
		];

		foreach ($macros as $class => $definitions) {
			foreach ($definitions as $name => $handler) {
				$class::macro($name, $handler);
			}
		}
	}
}
