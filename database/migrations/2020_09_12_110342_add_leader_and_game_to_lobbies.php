<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaderAndGameToLobbies extends Migration
{
	public function up()
	{
		Schema::table('lobbies', function (Blueprint $table) {
			$table->uuid('game_id')->index()->nullable();
			$table->foreign('game_id')->references('id')->on('games');

			$table->uuid('leader_id')->index();
			$table->foreign('leader_id')->references('id')->on('players')->onDelete('cascade');
		});
	}

	public function down()
	{
		Schema::table('lobbies', function (Blueprint $table) {
			$table->dropForeign('lobbies_game_id_foreign');
			$table->dropForeign('lobbies_leader_id_foreign');

			$table->dropIndex('lobbies_game_id_index');
			$table->dropIndex('lobbies_leader_id_index');

			$table->dropColumn(['game_id', 'leader_id']);
		});
	}
}
