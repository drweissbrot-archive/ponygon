<template>
	<div class="board">
		<div v-for="(row, x) in data.board" class="row">
			<button
				v-for="(node, y) in row"
				class="node"
				:disabled="node !== null || ! ownTurn"
				@click.prevent="claimNode(x, y)"
			>
				{{ x }} {{ y }} {{ node }}
			</button>
		</div>
	</div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
	props: {
		ownTurn: Boolean,
	},

	methods: {
		claimNode(x, y) {
			console.log('claiming node', x, y)
		},
	},

	computed: {
		...mapGetters({
			data: 'match/data',
		}),
	},
}
</script>
