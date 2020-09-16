<template>
	<div class="board">
		<div v-for="(row, x) in data.board" class="row">
			<button
				v-for="(node, y) in row"
				class="node"
				:disabled="node !== null || ! ownTurn"
				@click.prevent="claimNode(x, y)"
			>
				{{ (node || '&nbsp;').toUpperCase() }}
			</button>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
	props: {
		ownTurn: Boolean,
	},

	methods: {
		claimNode(x, y) {
			axios.post(`/api/match/${this.matchId}/move`, { x, y })
		},
	},

	computed: {
		...mapGetters({
			data: 'match/data',
			matchId: 'match/id',
		}),
	},
}
</script>
