<script>
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import settingsService from '@/services/settings.js';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';

export default {
	name: 'Addon',
	components: { ParameterForm, Info, Parameter },
	emits: ['addonSaved'],
	props: {
		addon: {
			type: Object,
			required: true,
		},
	},
	mixins: [transformIntoParameterField],
	data() {
		return {
			fields: [],
			groups: [],
			loading: true,
		};
	},
	created() {
		this.getAddonParameters();
	},
	methods: {
		async mountGroups(fields, values) {
			this.groups = await this.fieldsToParameterFormGroups(fields, values, 'table');
		},
		getAddonParameters() {
			settingsService
				.getAddonParameters(this.addon.namekey)
				.then((response) => {
					if (response.status) {
						this.fields = response.data;
						const values = this.addon.params;
						this.mountGroups(this.fields, values).then(() => {
							this.loading = false;
						});
					} else {
						this.loading = false;
					}
				})
				.catch((error) => {
					this.loading = false;
				});
		},
		onValueUpdated(param) {
			this.addon.params[param.param] = param.value;
		},
		saveAddon() {
			settingsService.saveAddon(this.addon).then((response) => {
				if (response.status) {
					Swal.fire({
						icon: 'success',
						title: this.translate('COM_EMUNDUS_SUCCESS'),
						text: this.translate('COM_EMUNDUS_ADDON_CONFIGURATION_SAVED'),
						showConfirmButton: false,
						delay: 2000,
					});
					this.$emit('addonSaved');
				} else {
					Swal.fire({
						icon: 'error',
						title: this.translate('COM_EMUNDUS_ERROR'),
						text: this.translate(response.message),
						showConfirmButton: false,
						delay: 2000,
					});
				}
			});
		},
	},
};
</script>

<template>
	<div
		id="addon-configuration"
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<h3>{{ translate(addon.name) }}</h3>
		<p>{{ translate(addon.description) }}</p>

		<ParameterForm v-if="!loading" :groups="groups" :fields="fields" @parameterValueUpdated="onValueUpdated" />

		<div id="actions" class="tw-flex tw-justify-end">
			<button class="tw-btn-primary" @click="saveAddon()">
				{{ translate('SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
