<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Button from '@/components/Atoms/Button.vue';

import groupsService from '@/services/groups.js';

export default {
	name: 'AssociateUsersModal',
	components: { Parameter, Button },
	props: {
		// Group ids selected in the list (bulk action path).
		items: Array,
	},
	emits: ['close', 'update-items'],
	data: () => ({
		saving: false,

		field: {
			param: 'users',
			type: 'multiselect',
			multiselectOptions: {
				noOptions: false,
				multiple: true,
				taggable: false,
				searchable: true,
				internalSearch: false,
				// Non-candidate users (manager profiles), shaped as { value, name }.
				asyncRoute: 'getavailablemanagers',
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
			label: 'COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_SELECT',
			placeholder: 'COM_EMUNDUS_USERS_SEARCH_PLACEHOLDER',
			displayed: true,
		},
	}),
	created() {
		if (!this.items || this.items.length === 0) {
			this.closeModal();
		}
	},
	methods: {
		closeModal() {
			this.$emit('close');
		},

		associateUsers() {
			if (this.disabledSubmit || this.saving) {
				return;
			}

			const userIds = this.field.value.map((user) => user.value);

			this.saving = true;
			groupsService.associateUsers(this.items, userIds).then((response) => {
				this.saving = false;

				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: this.translate('COM_EMUNDUS_GROUPS_USERS_ASSOCIATED'),
						showConfirmButton: false,
						allowOutsideClick: false,
						timer: 1500,
						customClass: {
							title: 'em-swal-title',
						},
					}).then(() => {
						this.$emit('update-items');
						this.closeModal();
					});
				} else {
					Swal.fire({
						icon: 'error',
						title: this.translate('COM_EMUNDUS_GROUPS_ERROR_ASSOCIATING_USERS'),
						text: response.msg,
						customClass: {
							title: 'em-swal-title',
						},
					});
				}
			});
		},
	},
	computed: {
		disabledSubmit() {
			return !Array.isArray(this.field.value) || this.field.value.length === 0;
		},

		confirmButton() {
			return this.translate('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_CONFIRM').replace(
				'{{groupCount}}',
				this.items ? this.items.length : 0,
			);
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-pt-4">
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<h2>{{ translate('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_ADD_USER') }}</h2>

				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="closeModal">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
		</div>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<Parameter
				ref="associate_users_field"
				:key="field.reload ? field.reload + field.param : field.param"
				:parameter-object="field"
				:help-text-type="'below'"
				:multiselect-options="field.multiselectOptions"
			/>
		</div>

		<div class="tw-mb-8 tw-mt-5 tw-flex tw-justify-between">
			<Button :variant="'cancel'" @click="closeModal">
				{{ translate('COM_EMUNDUS_ONBOARD_CANCEL') }}
			</Button>
			<Button :variant="'primary'" :disabled="disabledSubmit || saving" @click="associateUsers">
				{{ confirmButton }}
			</Button>
		</div>
	</div>
</template>

<style scoped></style>
