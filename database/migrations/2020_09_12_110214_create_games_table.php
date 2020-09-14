<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
	public function up()
	{
		Schema::create('games', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->timestamps();

			$table->string('game');
			$table->longText('config');
			$table->longText('state');

			$table->uuid('lobby_id')->index();
			$table->foreign('lobby_id')->references('id')->on('lobbies')->onDelete('cascade');
		});
	}

	public function down()
	{
		Schema::dropIfExists('games');
	}
}
