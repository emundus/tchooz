<template>
	<div>
		<iframe v-if="formUrl.length > 0" id="more-form-iframe" :src="formUrl" width="100%"></iframe>
	</div>
</template>

<script>
import campaignService from '@/services/campaign.js';
import Info from '@/components/Utils/Info.vue';

export default {
	name: 'CampaignMore',
	components: { Info },
	props: {
		campaignId: {
			type: Number,
			required: true,
		},
		defaultFormUrl: {
			type: String,
			required: false,
			default: '',
		},
	},
	data() {
		return {
			formUrl: '',

			needMoreInfo: false,
		};
	},
	created() {
		if (this.defaultFormUrl.length > 0) {
			this.formUrl = this.defaultFormUrl;
		} else {
			this.getFormUrl();
		}

		this.addEventListeners();
	},
	methods: {
		getFormUrl() {
			campaignService
				.getCampaignMoreFormUrl(this.campaignId)
				.then((response) => {
					if (response.status) {
						this.formUrl = response.data;
					}
				})
				.catch((error) => {
					console.error(error);
				});
		},

		addEventListeners() {
			window.addEventListener(
				'message',
				function (e) {
					if (e.data === 'askPublishCampaign') {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_CAMPAIGNS_MORE_PUBLISH_TITLE'),
							text: this.translate('COM_EMUNDUS_CAMPAIGNS_MORE_PUBLISH_TEXT'),
							showCancelButton: true,
							confirmButtonText: Joomla.Text._('JYES'),
							cancelButtonText: Joomla.Text._('JNO'),
							reverseButtons: true,
							customClass: {
								title: 'em-swal-title',
							},
						}).then((result) => {
							if (result.isConfirmed) {
								// Exécution de la requête AJAX pour publier la campagne
								campaignService.publishCampaign(this.campaignId).then((result) => {
									if (result.status) {
										Swal.fire({
											icon: 'success',
											title: this.translate('COM_EMUNDUS_CAMPAIGNS_MORE_PUBLISH_SUCCESS_TITLE'),
											text: this.translate('COM_EMUNDUS_CAMPAIGNS_MORE_PUBLISH_SUCCESS_TEXT'),
											reverseButtons: true,
											customClass: {
												title: 'em-swal-title',
											},
										});
									}
								});
							}
						});
					}
				}.bind(this),
			);
		},
	},
};
</script>

<style scoped>
#more-form-iframe {
	height: 50vh;
}
</style>
