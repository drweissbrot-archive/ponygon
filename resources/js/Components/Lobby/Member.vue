<template>
	<div class="member">
		<div class="name">
			{{ member.name }}
			<span v-if="isLeader" title="Lobby Leader">👑</span>
			<span v-if="isPlayer">you</span>
		</div>

		<div class="ready">
			{{ member.ready ? 'ready' : 'not ready' }}
		</div>

		<div v-if="leader.id === playerId && ! isLeader" class="leader-actions">
			<a href="#" @click.prevent="kick">kick</a>
			<a href="#" @click.prevent="promote">promote to leader</a>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
	props: {
		member: { required: true },
	},

	methods: {
		kick() {
			axios.post(`/api/lobby/${this.lobbyId}/kick`, { player: this.member.id })
		},

		promote() {
			axios.post(`/api/lobby/${this.lobbyId}/promote-to-leader`, { player: this.member.id })
		},
	},

	computed: {
		...mapGetters({
			leader: 'lobby/leader',
			lobbyId: 'lobby/id',
			playerId: 'player/id',
		}),

		isLeader() {
			return this.member.id === this.leader.id
		},

		isPlayer() {
			return this.member.id === this.playerId
		},
	},
}
</script>
