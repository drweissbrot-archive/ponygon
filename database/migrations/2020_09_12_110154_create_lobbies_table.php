<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLobbiesTable extends Migration
{
	public function up()
	{
		Schema::create('lobbies', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->timestamps();
			$table->softDeletes();

			$table->longText('game_config');
		});
	}

	public function down()
	{
		Schema::dropIfExists('lobbies');
	}
}
