<script>
import List from '@/views/List.vue';

export default {
	name: 'SogecommerceHistory',
	components: { List },
	props: {
		app: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			defaultList: {
				history: {
					title: 'COM_EMUNDUS_ONBOARD_SOGECOMMERCE_HISTORY',
					intro: 'COM_EMUNDUS_ONBOARD_SOGECOMMERCE_HISTORY_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_SOGECOMMERCE_HISTORY',
							key: 'history',
							controller: 'payment',
							getter: 'getTransationsQueueHistory&synchronizer_id=' + this.app.id,
							noData: 'COM_EMUNDUS_ONBOARD_SOGECOMMERCE_NO_HISTORY',
							actions: [
								{
									action: 'preview',
									label: 'COM_EMUNDUS_ONBOARD_VISUALIZE',
									controller: 'payment',
									name: 'preview',
									title: 'COM_EMUNDUS_ONBOARD_SOGECOMMERCE_HISTORY_DETAIL',
									method: (item) => {
										return this.previewHistory(item);
									},
								},
							],
							filters: [],
						},
					],
				},
			},
		};
	},
	methods: {
		previewHistory(item) {
			let html = '<div>';

			Object.keys(item.data).forEach((key) => {
				html +=
					'<div class="tw-grid tw-grid-cols-2">' +
					'<strong>' +
					key +
					'</strong>' +
					'<span>' +
					item.data[key] +
					'</span></div><hr>';
			});

			html += '</div>';

			return html;
		},
	},
};
</script>

<template>
	<div id="sogecommerce-history">
		<list :default-lists="defaultList" :default-type="'history'" :encoded="false"></list>
	</div>
</template>
