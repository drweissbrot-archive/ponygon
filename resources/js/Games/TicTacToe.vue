<template>
	<div class="game-tictactoe">
		<Player :player="{ id: playerId, name: playerName }" />

		<section class="playarea">
			<template v-if="data.winner === false">
				<h1 v-if="ownTurn">
					It’s your turn.
				</h1>

				<h1 v-else>
					It’s {{ otherPlayer.name }}’s turn.
				</h1>
			</template>

			<template v-else>
				<h1 v-if="data.winner === 'tie'">
					It’s a tie.
				</h1>

				<h1 v-else-if="data.winner === playerId">
					You win! Congratulations!
				</h1>

				<h1 v-else-if="data.winner === otherPlayer.id">
					{{ otherPlayer.name }} wins.
				</h1>

				<p class="rematch">
					Want a Rematch?

					<a href="#" @click.prevent="rematch">
						Play again
					</a>
				</p>

				<div v-if="isLobbyLeader" class="return-to-lobby">
					<a href="#" @click.prevent="endMatch">
						Return to Lobby
					</a>
				</div>
			</template>

			<div class="board">
				<Board :ownTurn="ownTurn" />
			</div>
		</section>

		<Player :player="otherPlayer" opponent />
	</div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import Board from '~Components/TicTacToe/Board'
import Player from '~Components/TicTacToe/Player'

export default {
	components: {
		Board,
		Player,
	},

	methods: {
		endMatch() {
			axios.post(`/api/match/${this.matchId}/end`)
		},

		rematch() {
			axios.post(`/api/match/${this.matchId}/rematch`)
		},
	},

	computed: {
		...mapGetters({
			data: 'match/data',
			isLobbyLeader: 'playerIsLeader',
			matchId: 'match/id',
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
