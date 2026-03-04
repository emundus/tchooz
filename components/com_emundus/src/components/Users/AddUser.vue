<script>
import Modal from '@/components/Modal.vue';
import userService from '@/services/user.js';
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import Button from '@/components/Atoms/Button.vue';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'AddUser',
	components: { Button, Parameter, Info, Modal },
	props: {
		item: Object,
	},
	emits: ['close', 'open'],
	mixins: [alerts],
	data: function () {
		return {
			fields: [
				{
					param: 'user',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: false,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getapplicants',
						asyncController: 'users',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						label: 'name',
						trackBy: 'value',
					},
					value: 0,
					reload: 0,
					label: 'COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD_APPLICANT',
					placeholder: '',
					displayed: true,
				},
			],
		};
	},
	created() {},
	methods: {
		beforeClose() {
			this.$emit('close');
		},
		beforeOpen() {
			this.$emit('open');
		},
		closeModal() {
			this.$emit('close');
		},
		addException() {
			const user = this.fields.find((field) => field.param === 'user').value;
			if (!user) {
				this.alertError('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD_ERROR');
				return;
			}

			userService.addException(user.value).then((response) => {
				console.log(response);
				if (!response.status) {
					this.alertError(response.msg);
					return;
				}

				this.alertSuccess('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD_SUCCESS');
				this.$emit('update-items');
				this.$emit('close');
			});
		},
	},
	computed: {},
};
</script>

<template>
	<div>
		<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
			<h3>
				{{ translate('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD') }}
			</h3>
			<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="closeModal">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<div
				v-for="field in fields"
				v-show="field.displayed && !field.hidden"
				:key="field.param"
				:class="'tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-2'"
			>
				<Parameter
					v-if="field.displayed"
					v-show="!field.hidden"
					:ref="'exception_add_user_' + field.param"
					:key="field.reload ? field.reload + field.param : field.param"
					:parameter-object="field"
					:help-text-type="'below'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
				/>
			</div>
		</div>

		<div class="tw-mt-6 tw-flex tw-justify-end">
			<Button @click="addException">
				{{ translate('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD') }}
			</Button>
		</div>
	</div>
</template>

<style scoped></style>
