<template>
	<section class="game-config">
		<div class="game-carousel">
			<button :disabled="! isLeader" @click.prevent="selectGame(-1)">
				&lt; <!-- TODO -->
			</button>

			<div class="heading">
				<h2>
					{{ games[gameConfig.selected_game].name }}
				</h2>

				<div class="players">
					{{ games[gameConfig.selected_game].players }}
				</div>
			</div>

			<button class="right" :disabled="! isLeader" @click.prevent="selectGame(1)">
				&gt; <!-- TODO -->
			</button>
		</div>

		<p v-if="! isLeader" class="only-leader-can-edit">
			Only the lobby leader can change the game or edit game settings.
		</p>

		<div class="settings">
			<p v-if="gameSettings.length < 1" class="no-settings">
				No settings available for this game.
			</p>

			<div
				v-if="key !== 'playerCount'"
				v-for="{ key, value } in gameSettings"
				:key="key"
				class="input-group"
			>
				<label :for="`game-config-${key}`">
					{{ key }}
				</label>

				<!-- TODO validation -->
				<input
					type="text"
					:disabled="! isLeader"
					:id="`game-config-${key}`"
					:value="value"
					@change.prevent="$emit('updateGameConfig', {
						key: `${gameConfig.selected_game}.${key}`,
						value: $event.target.value,
					})"
				>
			</div>
		</div>
	</section>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
	props: {
		games: { required: true },
	},

	methods: {
		selectGame(offset) {
			const games = Object.keys(this.games).sort()

			let index = games.indexOf(this.gameConfig.selected_game) + offset
			if (index >= games.length) index = 0
			else if (index < 0) index = games.length - 1

			this.$emit('updateGameConfig', { key: 'selected_game', value: games[index] })
		},
	},

	computed: {
		...mapGetters({
			gameConfig: 'lobby/gameConfig',
			isLeader: 'playerIsLeader',
		}),

		gameSettings() {
			if (! this.gameConfig.selected_game) return []

			const settings = []

			for (const setting in this.gameConfig[this.gameConfig.selected_game]) {
				if (setting === 'playerCount') continue

				settings.push({
					key: setting,
					value: this.gameConfig[this.gameConfig.selected_game][setting]
				})
			}

			return settings
		},
	},
}
</script>
