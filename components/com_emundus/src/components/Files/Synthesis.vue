<script>
import fileService from '@/services/file.js';

export default {
	name: 'Synthesis',
	props: {
		fnum: {
			type: String,
			required: true,
		},
		content: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			synthesis: '',
			loading: true,
			error: false,
		};
	},
	mounted() {
		if (!this.content) {
			this.getSynthesis();
		} else {
			this.synthesis = this.content;
			this.loading = false;
		}
	},
	methods: {
		getSynthesis() {
			this.loading = true;
			this.error = false;

			fileService
				.getFileSynthesis(this.fnum)
				.then((response) => {
					this.synthesis = response.data;
					this.loading = false;
				})
				.catch((error) => {
					this.error = true;
					this.loading = false;
					console.error('Error fetching synthesis:', error);
				});
		},
	},
};
</script>

<template>
	<div id="application-synthesis" class="tw-m-4 tw-rounded tw-border tw-p-4">
		<div v-if="!loading && !error" v-html="synthesis"></div>
	</div>
</template>

<style scoped></style>
