<script>
import Button from '@/components/Atoms/Button.vue';
import groupsService from '@/services/groups.js';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	name: 'AccessRightsTable',
	props: {
		group: {
			type: Object,
			required: true,
		},
		canUpdate: {
			type: Boolean,
			default: false,
		},
	},
	components: { Loader, Button },
	data: () => ({
		loading: false,

		searchThroughActions: '',
		allActionRightsChecked: {},
	}),
	created() {
		if (!this.group.access_rights) {
			this.loading = true;
			groupsService.getAccessRights(this.group.id).then((response) => {
				if (response.status) {
					this.group.access_rights = response.data;
					this.loading = false;

					this.resourceChecked();
				}
			});
		} else {
			this.resourceChecked();
		}
	},
	methods: {
		displayHelpMessage(description) {
			Swal.fire({
				position: 'center',
				icon: 'info',
				title: description,
				showConfirmButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_CLOSE'),
				allowOutsideClick: true,
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					actions: 'em-swal-single-action',
				},
			});
		},

		updateRights() {
			let rightsToUpdate = [];

			Object.keys(this.group.access_rights).forEach((category) => {
				this.group.access_rights[category].forEach((right) => {
					rightsToUpdate.push({
						id: right.id,
						action_id: right.action.id,
						crud: right.crud,
					});
				});
			});

			groupsService.updateAccessRights(this.group.id, rightsToUpdate).then((response) => {
				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: response.msg,
						showConfirmButton: true,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					});
				} else {
					// Handle error
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
			});
		},

		computeRightState(right) {
			const applicableCruds = Object.keys(right.action.crud).filter(
				(crud) => crud !== 'multi' && right.action.crud[crud] == 1,
			);

			if (applicableCruds.length === 0) {
				return 'none';
			}

			const checkedCount = applicableCruds.filter((crud) => right.crud[crud] == 1).length;

			if (checkedCount === applicableCruds.length) {
				return 'all';
			} else if (checkedCount > 0) {
				return 'some';
			}
			return 'none';
		},

		resourceChecked(right = null) {
			if (!right) {
				Object.values(this.group.access_rights).forEach((category) => {
					category.forEach((r) => {
						this.allActionRightsChecked[r.action.id] = this.computeRightState(r);
					});
				});
			} else {
				this.allActionRightsChecked[right.action.id] = this.computeRightState(right);
			}

			this.$nextTick(() => {
				this.applyIndeterminateState();
			});
		},

		applyIndeterminateState() {
			Object.keys(this.allActionRightsChecked).forEach((actionId) => {
				const ref = this.$refs['check-all-right-' + actionId];
				if (ref) {
					const el = Array.isArray(ref) ? ref[0] : ref;
					if (el) {
						el.indeterminate = this.allActionRightsChecked[actionId] === 'some';
					}
				}
			});
		},

		checkAllActionRights(right) {
			const newValue = this.allActionRightsChecked[right.action.id] === 'all' ? '0' : '1';

			Object.keys(right.action.crud).forEach((crud) => {
				if (crud !== 'multi' && right.action.crud[crud] == 1) {
					right.crud[crud] = newValue;
				}
			});

			this.resourceChecked(right);
		},
	},
	computed: {
		displayedRights() {
			if (!this.group || !this.group.access_rights) return [];

			let rights = [];
			Object.keys(this.group.access_rights).forEach((category) => {
				this.group.access_rights[category].forEach((right) => {
					if (right.action.label.toLowerCase().includes(this.searchThroughActions.toLowerCase())) {
						rights.push(right);
					}
				});
			});

			return rights;
		},
	},
};
</script>

<template>
	<div class="tw-mt-6">
		<div v-if="!loading">
			<div v-if="canUpdate">
				<p v-html="translate('COM_EMUNDUS_GROUPS_SHOW_RIGHTS_INTRO')" />
				<div class="tw-mb-7 tw-mt-4 tw-flex tw-justify-end">
					<Button @click="updateRights">
						{{ translate('COM_EMUNDUS_GROUPS_EDIT_SAVE') }}
					</Button>
				</div>
			</div>

			<input
				type="text"
				v-model="searchThroughActions"
				:placeholder="translate('COM_EMUNDUS_ACTION_SEARCH_PLACEHOLDER')"
				class="tw-mb-4 tw-w-full tw-rounded tw-border tw-border-neutral-300 tw-p-2"
			/>

			<div class="tw-mt-6" v-for="(actionCategory, index) in this.group.access_rights">
				<h3>
					{{ translate('COM_EMUNDUS_ACTION_TYPE_' + index.toUpperCase()) }}
				</h3>
				<div
					class="tw-relative tw-mb-6 tw-mt-3 tw-max-h-dvh tw-overflow-scroll tw-rounded-coordinator tw-border tw-border-neutral-300"
				>
					<!-- header -->
					<div
						class="tw-sticky tw-top-0 tw-z-10 tw-grid tw-bg-neutral-100 tw-p-3"
						style="grid-template-columns: 50% repeat(4, minmax(0, 1fr))"
					>
						<label class="!tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ACTION_RESOURCE') }}</label>
						<div class="tw-flex tw-items-center">
							<label for="check-all" class="!tw-mb-0 tw-cursor-pointer tw-font-medium">
								{{ translate('COM_EMUNDUS_ACTION_CREATE') }}
							</label>
						</div>
						<div class="tw-flex tw-items-center">
							<label for="check-all" class="!tw-mb-0 tw-cursor-pointer tw-font-medium">
								{{ translate('COM_EMUNDUS_ACTION_READ') }}
							</label>
						</div>
						<div class="tw-flex tw-items-center">
							<label for="check-all" class="!tw-mb-0 tw-cursor-pointer tw-font-medium">
								{{ translate('COM_EMUNDUS_ACTION_UPDATE') }}
							</label>
						</div>
						<div class="tw-flex tw-items-center">
							<label for="check-all" class="!tw-mb-0 tw-cursor-pointer tw-font-medium">
								{{ translate('COM_EMUNDUS_ACTION_DELETE') }}
							</label>
						</div>
					</div>

					<div>
						<div
							v-for="right in displayedRights"
							v-show="right.action.type === index"
							class="tw-grid tw-p-3 hover:tw-bg-neutral-200"
							style="grid-template-columns: 50% repeat(4, minmax(0, 1fr))"
						>
							<div class="tw-flex tw-items-center">
								<input
									v-show="canUpdate"
									:ref="'check-all-right-' + right.action.id"
									class="tw-cursor-pointer"
									type="checkbox"
									:checked="allActionRightsChecked[right.action.id] === 'all'"
									@change="checkAllActionRights(right)"
									:disabled="!canUpdate"
								/>
								<label class="tw-mb-0">{{ right.action.label }}</label>
								<span
									v-if="right.action.description && canUpdate"
									class="material-symbols-outlined tw-ml-2 tw-cursor-pointer"
									@click="displayHelpMessage(right.action.description)"
									>help</span
								>
							</div>
							<div class="tw-flex tw-items-center">
								<input
									v-show="right.action.crud.create === 1"
									id="check-create"
									class="tw-cursor-pointer"
									type="checkbox"
									false-value="0"
									true-value="1"
									v-model="right.crud.create"
									@change="resourceChecked(right)"
									:disabled="!canUpdate"
								/>
							</div>
							<div class="tw-flex tw-items-center">
								<input
									v-show="right.action.crud.read === 1"
									id="check-all-read"
									class="tw-cursor-pointer"
									type="checkbox"
									false-value="0"
									true-value="1"
									v-model="right.crud.read"
									@change="resourceChecked(right)"
									:disabled="!canUpdate"
								/>
							</div>
							<div class="tw-flex tw-items-center">
								<input
									v-show="right.action.crud.update === 1"
									id="check-all-update"
									class="tw-cursor-pointer"
									type="checkbox"
									false-value="0"
									true-value="1"
									v-model="right.crud.update"
									@change="resourceChecked(right)"
									:disabled="!canUpdate"
								/>
							</div>
							<div class="tw-flex tw-items-center">
								<input
									v-show="right.action.crud.delete === 1"
									id="check-all-delete"
									class="tw-cursor-pointer"
									type="checkbox"
									false-value="0"
									true-value="1"
									v-model="right.crud.delete"
									@change="resourceChecked(right)"
									:disabled="!canUpdate"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tw-mt-7 tw-flex tw-justify-end" v-if="canUpdate">
				<Button @click="updateRights">
					{{ translate('COM_EMUNDUS_GROUPS_EDIT_SAVE') }}
				</Button>
			</div>
		</div>
		<Loader v-else />
	</div>
</template>

<style scoped></style>
