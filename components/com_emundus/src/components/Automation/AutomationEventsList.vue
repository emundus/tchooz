<script>
export default {
	name: 'AutomationEventsList',
	props: {
		events: {
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
		onSelectEvent(event) {
			this.$emit('select-event', event);
		},
	},
	computed: {
		eventsByGroups() {
			const groups = {};
			this.filteredEvents.forEach((event) => {
				if (!groups[event.category.value]) {
					groups[event.category.value] = {
						label: event.category.label,
						icon: event.category.icon,
						events: [],
					};
				}
				groups[event.category.value].events.push(event);
			});

			return groups;
		},
		filteredEvents() {
			if (!this.searchQuery) {
				return this.events;
			}
			const query = this.searchQuery.toLowerCase();
			return this.events.filter(
				(event) =>
					event.label.toLowerCase().includes(query) ||
					(event.description && event.description.toLowerCase().includes(query)),
			);
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-flex tw-h-full tw-flex-col">
			<div class="search tw-mt-4 tw-w-full">
				<div class="tw-flex tw-flex-col">
					<label>{{ translate('COM_EMUNDUS_AUTOMATION_SEARCH_EVENTS') }}</label>
					<input
						id="search-inputx"
						type="text"
						:placeholder="translate('COM_EMUNDUS_AUTOMATION_SEARCH_EVENTS_PLACEHOLDER')"
						class="tw-mb-4 tw-w-full tw-rounded-md tw-border tw-border-neutral-300 tw-px-4 tw-py-2"
						v-model="searchQuery"
					/>
				</div>
			</div>

			<div class="tw-flex tw-h-[55vh] tw-flex-col tw-gap-4 tw-overflow-y-auto">
				<div v-for="group in Object.keys(eventsByGroups)" :key="group" class="tw-mt-4">
					<h3 class="tw-mb-2 tw-text-xl tw-font-semibold">
						{{ translate(eventsByGroups[group].label) }}
					</h3>
					<div class="tw-grid tw-grid-cols-4 tw-gap-4 tw-pb-4">
						<div
							v-for="event in eventsByGroups[group].events"
							:key="event.name"
							class="automation-event tw-cursor-pointer tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
							@click="onSelectEvent(event)"
						>
							<p>
								<strong>{{ event.label }}</strong>
							</p>
							<p v-if="event.description.length > 0" class="tw-mt-4">{{ event.description }}</p>
						</div>
					</div>
				</div>
			</div>
			<div class="actions tw-mt-4 tw-flex tw-w-full tw-justify-end">
				<button class="tw-btn-primary" @click="$emit('close')">{{ translate('COM_EMUNDUS_CLOSE') }}</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
