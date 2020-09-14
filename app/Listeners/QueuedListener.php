<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

abstract class QueuedListener extends Listener implements ShouldQueue
{
	use InteractsWithQueue;
}
