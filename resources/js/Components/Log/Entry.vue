<template>
	<div :class="['entry', { '--log': ! entry.isChat }]" :title="at">
		{{ entry.message }}
	</div>
</template>

<script>
export default {
	props: {
		entry: { required: true },
	},

	computed: {
		at() {
			return new Date(this.entry.at).toLocaleTimeString()
		},
	},
}
</script>
