<template>
	<div id="app">
		<div
			class="tw-relative tw-mb-6 tw-flex tw-min-h-[140px] tw-w-full tw-flex-wrap tw-rounded-coordinator-cards tw-shadow-standard"
		>
			<div v-if="displayShapes == 1" id="background-shapes"></div>
			<div class="tw-mb-0 tw-w-full" style="z-index: 3">
				<div class="tw-relative tw-flex tw-flex-col tw-gap-2 tw-rounded-coordinator-cards tw-p-8">
					<h1 class="tw-text-white">
						{{ displayProfileName }}
					</h1>

					<p v-if="displayName == 1" class="tw-text-sm tw-text-white">
						{{ translate('COM_EMUNDUS_DASHBOARD_HELLO') }} {{ name }}
						{{ translate('COM_EMUNDUS_DASHBOARD_WELCOME') }}
					</p>
					<p v-if="displayDescription == 1" class="tw-break-all tw-text-sm tw-text-white">
						{{ profile_description }}
					</p>
				</div>

				<div v-if="displayTchoozy == 1" id="background-tchoozy"></div>
			</div>
			<div
				class="tw-absolute tw-z-0 tw-m-0 tw-h-full tw-w-full tw-rounded-coordinator-cards tw-bg-profile-full tw-p-8"
			></div>
		</div>

		<div v-if="widgets.length > 0">
			<div
				v-if="programmeFilter == 1"
				class="tw-mb-6 !tw-flex tw-flex-col !tw-gap-0 tw-rounded-coordinator-cards tw-bg-neutral-0 tw-p-8 tw-shadow-standard"
				style="width: calc(50% - 12px)"
			>
				<label class="tw-text-neutral-900">{{ translate('COM_EMUNDUS_DASHBOARD_FILTER_BY_PROGRAMMES') }}</label>
				<select v-model="selectedProgramme" class="form-control fabrikinput tw-w-full tw-cursor-pointer">
					<option value="" selected>
						{{ translate('COM_EMUNDUS_DASHBOARD_ALL_PROGRAMMES') }}
					</option>
					<option v-for="programme in programmes" v-bind:key="programme.id" :value="programme.code">
						{{ programme.label['fr'] }}
					</option>
				</select>
			</div>

			<div :class="'tw-grid-cols- tw-grid tw-gap-3' + this.grid_size">
				<div
					v-for="(widget, index) in widgets"
					:id="widget.name + '_' + index"
					:class="widget.name + '-' + widget.class"
					:key="widget.name + '_' + index"
				>
					<Custom v-if="widget.name === 'custom'" :widget="widget" @forceUpdate="$forceUpdate" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import dashboardService from '@/services/dashboard';

import Custom from '@/components/Dashboard/Custom.vue';

export default {
	name: 'App',
	props: {
		programmeFilter: Number,
		displayDescription: Number,
		displayName: Number,
		displayShapes: Number,
		displayTchoozy: Number,
		name: String,
		language: Number,
		profile_name: String,
		profile_description: String,
		grid_size: Number,
	},
	components: {
		Custom,
	},
	data() {
		return {
			programmes: [],
			selectedProgramme: null,
			widgets: [],
			colors: '',
			status: null,
			enableDrag: false,

			reloadWidgets: 0,
		};
	},
	created() {
		this.getWidgets();
		if (this.programmeFilter == 1) {
			this.getProgrammes();
		}
	},
	methods: {
		getWidgets() {
			dashboardService.getWidgets().then((response) => {
				this.widgets = response.data ? response.data : [];
			});
		},

		getProgrammes() {
			dashboardService.getProgrammes().then((response) => {
				this.programmes = response.data.datas;
				dashboardService.getFilterProgramme().then((response) => {
					this.selectedProgramme = response.data;
				});
			});
		},

		setFilterProgramme(programme) {
			dashboardService.setFilterProgramme(programme).then((response) => {
				this.widgets = [];
				this.getWidgets();
			});
		},
	},
	computed: {
		displayProfileName() {
			if (this.language == 1) {
				return this.profile_name !== ''
					? this.translate('COM_EMUNDUS_DASHBOARD_AREA') + ' ' + this.profile_name.toLowerCase()
					: this.translate('COM_EMUNDUS_DASHBOARD_AREA') + ' ' + this.translate('COM_EMUNDUS_DASHBOARD_EMPTY_LABEL');
			} else {
				return this.profile_name !== ''
					? this.profile_name + ' ' + this.translate('COM_EMUNDUS_DASHBOARD_AREA').toLowerCase()
					: this.translate('COM_EMUNDUS_DASHBOARD_EMPTY_LABEL') + ' ' + this.translate('COM_EMUNDUS_DASHBOARD_AREA');
			}
		},
	},
	watch: {
		selectedProgramme: function (val) {
			this.setFilterProgramme(val);
		},
	},
};
</script>

<style scoped>
#background-tchoozy {
	mask-image: url('../../../../../media/com_emundus/images/tchoozy/complex-illustrations/tchoozy-gestionnaire.svg');
	-webkit-mask-image: url('../../../../../media/com_emundus/images/tchoozy/complex-illustrations/tchoozy-gestionnaire.svg');
	background: var(--neutral-0);
	mask-repeat: no-repeat;
	-webkit-mask-repeat: no-repeat;
	mask-size: 160px;
	-webkit-mask-size: 160px;
	mask-position: center;
	-webkit-mask-position: center;
	mask-origin: border-box;
	-webkit-mask-origin: border-box;
	z-index: 3;
	position: absolute;
	height: 100%;
	bottom: 0;
	opacity: 0.5;
	right: 0;
	top: 0;
	width: 228px;
	display: var(--logged-homepage);
}

#background-shapes {
	mask-image: url('../../../../../modules/mod_emundus_campaign/assets/fond-fonce.svg');
	-webkit-mask-image: url('../../../../../modules/mod_emundus_campaign/assets/fond-fonce.svg');
	background: var(--neutral-0);
	mask-repeat: no-repeat;
	-webkit-mask-repeat: no-repeat;
	mask-size: 500px;
	-webkit-mask-size: 500px;
	mask-origin: border-box;
	-webkit-mask-origin: border-box;
	mask-position: center;
	-webkit-mask-position: center;
	z-index: 3;
	transform: rotate(180deg);
	position: absolute;
	left: 0;
	opacity: 0.5;
	height: 100%;
	top: 0;
	width: 463px !important;
}
</style>
