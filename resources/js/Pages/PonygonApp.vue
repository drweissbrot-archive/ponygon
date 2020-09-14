<template>
	<div>
		<Join v-if="lobbyId === null" />

		<Lobby v-else-if="lobbyId !== null" />

		<!-- <component :is="state.game" v-else-if="state.type === 'game'" :state="state" /> -->
	</div>
</template>

<script>
import { mapGetters } from 'vuex'
import Join from './Join'
import Lobby from './Lobby'

export default {
	components: {
		Join,
		Lobby,
	},

	// data() {
	// 	return {
	// 		state: {
	// 			type: 'join',
	// 		},
	// 	}
	// },

	computed: {
		...mapGetters({
			lobbyId: 'lobby/id',
		}),
	},
}
</script>
