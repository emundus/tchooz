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
			loading: false,
			error: false,
		};
	},
	mounted() {
		if (!this.content) {
			this.getSynthesis();
		} else {
			this.synthesis = this.content;
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
	<div
		id="application-synthesis"
		class="tw-border tw-bg-white tw-p-4 tw-shadow-card"
		style="border-radius: 0 8px 8px 0"
	>
		<div v-if="!loading">
			<h3>{{ translate('COM_EMUNDUS_APPLICATION_SYNTHESIS') }}</h3>
		</div>
		<div class="tw-mt-3" v-if="!loading && !error" v-html="synthesis"></div>
	</div>
</template>

<style scoped></style>
