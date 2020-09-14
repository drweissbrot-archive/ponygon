<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayersTable extends Migration
{
	public function up()
	{
		Schema::create('players', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->timestamps();
			$table->softDeletes();

			$table->string('name');
			$table->string('token');

			$table->boolean('ready')->default(false);

			$table->rememberToken();

			$table->uuid('lobby_id')->index()->nullable();
			$table->foreign('lobby_id')->references('id')->on('lobbies');
		});
	}

	public function down()
	{
		Schema::dropIfExists('players');
	}
}
