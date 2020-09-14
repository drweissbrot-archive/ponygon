<template>
	<div class="log">
		<Entry v-for="(entry, index) in entries" :key="index" :entry="entry" />
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
}
</script>
