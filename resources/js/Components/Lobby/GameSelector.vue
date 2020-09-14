<template>
	<section class="game-config --selector">
		<h2>
			Select a Game
		</h2>

		<p v-if="! isLeader" class="only-leader-can-edit">
			Only the lobby leader can select a game.
		</p>

		<button
			v-for="(game, alias) in games"
			class="game"
			:disabled="! isLeader"
			@click.prevent="$emit('updateGameConfig', { key: 'selected_game', value: alias })"
		>
			<span class="name">
				{{ game.name }}
			</span>

			<span class="players">
				{{ game.players }}
			</span>
		</button>
	</section>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
	props: {
		games: { required: true },
	},

	computed: {
		...mapGetters({
			isLeader: 'playerIsLeader',
			members: 'lobby/members',
		}),
	},
}
</script>
