<template>
	<section :class="['player', { '--self': ! opponent, '--opponent': opponent }]">
		<div class="wrapper">
			<h2>
				{{ player.name }}
			</h2>

			<div class="score">
				{{ scoreboardData[player.id].score }} won
			</div>
		</div>

		<div class="symbol">
			{{ scoreboardData[player.id].role }}
		</div>
	</section>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
	props: {
		opponent: Boolean,
		player: { required: true },
	},

	computed: {
		...mapGetters({
			scoreboardData: 'match/scoreboardData',
		}),
	},
}
</script>
