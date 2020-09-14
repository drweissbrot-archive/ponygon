<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class QueuedJob extends Job implements ShouldQueue
{
	use InteractsWithQueue, SerializesModels;
}
