<template>
	<div id="iframe-tab-custom">
		<iframe
			v-if="finishedUrl"
			:src="finishedUrl"
			class="h-full"
			id="iframe-custom"
			@load="loading = false"
			title="Iframe tab"
		/>
	</div>
</template>

<script>
import fileService from '@/services/file';

export default {
	name: 'IframeTab',
	props: {
		url: {
			type: String,
			required: true,
		},
		fnum: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: false,
			applicant_id: 0,
			campaign_id: 0,
			finishedUrl: '',
		};
	},
	mounted() {
		this.loading = true;
		this.replacePatterns();

		let topWrapper = document.getElementById('iframe-tabs');
		if (topWrapper) {
			topWrapper.style.height = 'calc(100vh - ' + topWrapper.getBoundingClientRect().top + 'px)';
		}
	},
	methods: {
		replacePatterns() {
			let url = this.url;

			fileService.getFnumInfos(this.fnum).then((response) => {
				if (url.includes('{fnum}')) {
					url = this.url.replace('{fnum}', this.fnum);
				}

				this.applicant_id = response.fnumInfos.applicant_id;
				this.campaign_id = response.fnumInfos.campaign_id;
				url = url.replace('{applicant_id}', this.applicant_id).replace('{campaign_id}', this.campaign_id);

				if (url.includes('[') && url.includes(']')) {
					fileService.renderEmundusTags(url, this.fnum).then((response) => {
						if (response.status) {
							this.finishedUrl = response.data;
						} else {
							this.finishedUrl = url;
						}
					});
				} else {
					this.finishedUrl = url;
				}
			});
		},
	},
};
</script>

<style>
#iframe-tab-custom {
	height: 100vh;
}
#iframe-custom {
	height: 100%;
	width: 100%;
}
</style>
