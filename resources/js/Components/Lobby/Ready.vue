<template>
	<div class="ready">
		<div class="button">
			<button @click.prevent="toggleReady">
				<template v-if="ready">
					Unready
				</template>

				<template v-else>
					Ready
				</template>
			</button>
		</div>

		<div class="note">
			<div v-if="starting === false">
				<strong class="text-bold" v-if="ready">
					You are ready.
				</strong>

				<strong class="text-bold" v-else>
					You are not ready.
				</strong>

				<template v-if="gameConfig.selected_game">
					The game will begin as soon as all players are ready.
				</template>

				<template v-else>
					The Lobby Leader needs to select a game.
					Mark yourself as ready when you are.
				</template>
			</div>

			<template v-else>
				Game will begin in

				<div class="starting">
					{{ starting }}
				</div>
			</template>
		</div>
	</div>
</template>

<script>
import echo from '~Echo'
import { mapActions, mapGetters } from 'vuex'

export default {
	data() {
		return {
			starting: false,
			startingInterval: null,
		}
	},

	mounted() {
		// TODO this will probably cause some issues when the lobby is unmounted and then re-mounted lateron (e.g. after a game has finished) (might not though not sure)
		echo.singleLobby(this.lobbyId)
			.listen('Lobby\\MatchWillStart', (e) => {
				this.starting = 4
				this.startingInterval = setInterval(() => {
					if (--this.starting < 1) clearInterval(this.startingInterval)
				}, 1000)
			})
			.listen('Lobby\\MatchCancelled', (e) => {
				this.starting = false
				clearInterval(this.startingInterval)
			})
	},

	methods: {
		...mapActions({
			toggleReady: 'player/toggleReady',
		}),
	},

	computed: {
		...mapGetters({
			gameConfig: 'lobby/gameConfig',
			lobbyId: 'lobby/id',
			ready: 'player/ready',
		}),
	},
}
</script>
