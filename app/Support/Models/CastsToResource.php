<?php

namespace App\Support\Models;

use Illuminate\Http\Resources\Json\JsonResource;

trait CastsToResource
{
	public function resource() : JsonResource
	{
		$fqcn = $this->getResourceFqcn();

		return new $fqcn($this);
	}

	public static function resourceCollection(iterable $items) : JsonResource
	{
		return (static::getResourceFqcn())::collection($items);
	}

	public static function getResourceFqcn()
	{
		return '\App\Http\Resources\\' . class_basename(static::class) . 'Resource';
	}
}
