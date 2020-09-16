<template>
	<div>
		<component
			:is="`Game${game.charAt(0).toUpperCase() + game.slice(1)}`"
			v-if="lobbyId && matchId && game"
		/>

		<Lobby v-else-if="lobbyId !== null" />

		<Join v-else />
	</div>
</template>

<script>
import { mapGetters } from 'vuex'
import Join from './Join'
import Lobby from './Lobby'
import GameTictactoe from '~Games/TicTacToe'

export default {
	components: {
		Join,
		Lobby,

		GameTictactoe,
	},

	computed: {
		...mapGetters({
			lobbyId: 'lobby/id',
			matchId: 'match/id',
			game: 'match/game',
		}),
	},
}
</script>
