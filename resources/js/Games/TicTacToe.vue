<template>
	<div class="game-tictactoe">
		<Player :player="{ id: playerId, name: playerName }" />

		<section class="playarea">
			<h1 v-if="ownTurn">
				It’s your turn.
			</h1>

			<h1 v-else>
				It’s {{ otherPlayer.name }}’s turn.
			</h1>

			<div class="board">
				<Board :ownTurn="ownTurn" />
			</div>
		</section>

		<Player :player="otherPlayer" opponent />
	</div>
</template>

<script>
import { mapGetters } from 'vuex'
import Board from '~Components/TicTacToe/Board'
import Player from '~Components/TicTacToe/Player'

export default {
	components: {
		Board,
		Player,
	},

	computed: {
		...mapGetters({
			data: 'match/data',
			members: 'lobby/members',
			playerId: 'player/id',
			playerName: 'player/name',
		}),

		ownTurn() {
			return this.data.turn === this.playerId
		},

		otherPlayer() {
			return this.members.find(({ id }) => id !== this.playerId)
		},
	},
}
</script>
