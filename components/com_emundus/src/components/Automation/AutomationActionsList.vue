<script>
export default {
	name: 'AutomationActionsList',
	props: {
		actions: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			searchQuery: '',
		};
	},
	methods: {
		selectAction(action) {
			this.$emit('select-action', action);
		},
	},
	computed: {
		actionsByGroups() {
			const groups = {};
			this.filteredActions.forEach((action) => {
				if (!groups[action.category.value]) {
					groups[action.category.value] = {
						label: action.category.label,
						icon: action.category.icon,
						actions: [],
					};
				}
				groups[action.category.value].actions.push(action);
			});

			return groups;
		},
		filteredActions() {
			if (!this.searchQuery) {
				return this.actions;
			}
			const query = this.searchQuery.toLowerCase();
			return this.actions.filter(
				(action) =>
					action.label.toLowerCase().includes(query) ||
					(action.description && action.description.toLowerCase().includes(query)),
			);
		},
	},
};
</script>

<template>
	<div>
		<div v-if="actions.length === 0">
			<p>COM_EMUNDUS_AUTOMATION_NO_ACTIONS</p>
		</div>
		<div v-else class="tw-flex tw-h-full tw-flex-col">
			<div>
				<label class="tw-mt-4">
					{{ translate('COM_EMUNDUS_AUTOMATION_SEARCH_ACTIONS') }}
				</label>
				<input
					id="search-input"
					type="text"
					:placeholder="translate('COM_EMUNDUS_AUTOMATION_SEARCH_ACTIONS_PLACEHOLDER')"
					class="tw-mb-4 tw-w-full tw-rounded-md tw-border tw-border-neutral-300 tw-px-4 tw-py-2"
					v-model="searchQuery"
				/>
			</div>

			<div class="tw-flex tw-h-[55vh] tw-flex-col tw-gap-4 tw-overflow-y-auto">
				<div v-for="group in Object.keys(actionsByGroups)" :key="group" class="tw-mt-4">
					<h3 class="tw-mb-2 tw-text-xl tw-font-semibold">
						{{ translate(actionsByGroups[group].label) }}
					</h3>
					<div class="tw-grid tw-grid-cols-4 tw-gap-4 tw-pb-4">
						<div
							v-for="action in actionsByGroups[group].actions"
							:key="action.id"
							class="tw-max-h-64 tw-cursor-pointer tw-overflow-hidden tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
							@click="selectAction(action)"
						>
							<div class="tw-h-[32px] tw-w-[32px] tw-rounded-coordinator tw-bg-blue-100 tw-pl-1">
								<span
									class="material-symbols-outlined tw-h-[20px] tw-w-[20px] !tw-text-2xl tw-font-bold tw-text-blue-600"
									>{{ action.icon }}</span
								>
							</div>
							<h4 class="tw-mb-4 tw-mt-4 tw-text-lg tw-font-medium">{{ translate(action.label) }}</h4>
							<p class="tw-mb-2">{{ translate(action.description) }}</p>
						</div>
					</div>
				</div>
			</div>

			<div class="tw-flex tw-w-full tw-justify-end">
				<button id="close-actions-list" @click="$emit('close')" class="tw-btn-secondary tw-mt-4">
					{{ translate('COM_EMUNDUS_CLOSE') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
