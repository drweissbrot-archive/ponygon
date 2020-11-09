<template>
	<div class="log">
		<Entry ref="entries" v-for="(entry, index) in entries" :key="index" :entry="entry" />
	</div>
</template>

<script>
import Entry from './Entry'

export default {
	components: {
		Entry,
	},

	props: {
		entries: { required: true },
	},

	mounted() {
		this.scrollLatestIntoView()
	},

	methods: {
		scrollLatestIntoView() {
			this.$refs.entries[this.$refs.entries.length - 1].$el.scrollIntoView()
		},
	},

	watch: {
		entries(now, previously) {
			this.scrollLatestIntoView()
		},
	},
}
</script>
