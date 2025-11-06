<script>
import Card from '@/components/Molecules/Card.vue';
import Button from '@/components/Atoms/Button.vue';

import date from '@/mixins/date.js';
import string from '@/mixins/string.js';
import campaign from '@/mixins/campaign.js';
import NoResults from '@/components/Utils/NoResults.vue';
import Filter from '@/components/List/Filter.vue';

export default {
	name: 'CampaignsList',
	mixins: [date, string, campaign],
	components: { Filter, NoResults, Button, Card },
	emits: ['apply', 'update-filter', 'search'],
	props: {
		campaigns: {
			type: Array,
			required: true,
		},
		filters: {
			type: Array,
		},
		applyText: {
			type: String,
			default: 'COM_EMUNDUS_CAMPAIGNS_APPLY_NOW',
		},
	},
	data() {
		return {
			search: '',
			searchDebounce: null,
		};
	},
	methods: {
		applyToCampaign(campaignId) {
			this.$emit('apply', campaignId);
		},
		searchItems() {
			if (this.searchDebounce !== null) {
				clearTimeout(this.searchDebounce);
			}

			this.searchDebounce = setTimeout(() => {
				this.$emit('search', this.search);
			}, 500);
		},
		filterProperties(properties) {
			let filtered = {};
			for (const [key, property] of Object.entries(properties)) {
				if (!property.hidden) {
					filtered[key] = property;
				}
			}
			return filtered;
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-mb-4 tw-flex tw-w-full tw-items-end tw-justify-between">
			<!-- Filters -->
			<div class="tw-flex tw-items-center tw-gap-3">
				<Filter
					v-for="filter in filters"
					:key="filter.key"
					:filter="filter"
					@change-filter="$emit('update-filter', $event)"
				/>
			</div>

			<!-- Searchbar -->
			<div class="tw-flex tw-flex-col tw-items-end">
				<label class="!tw-mb-0 tw-font-medium tw-opacity-0">{{ translate('COM_EMUNDUS_ONBOARD_SEARCH') }}</label>
				<div class="tw-flex tw-min-w-[15rem] tw-items-center">
					<input
						name="search"
						type="text"
						v-model="search"
						:placeholder="translate('COM_EMUNDUS_ONBOARD_SEARCH')"
						class="tw-m-0 !tw-rounded-coordinator-form"
						@change="searchItems"
						@keyup="searchItems"
					/>
					<span class="material-symbols-outlined tw-ml-[-32px] tw-mr-2 tw-cursor-pointer" @click="searchItems">
						search
					</span>
				</div>
			</div>
		</div>

		<div class="tw-grid tw-gap-4 sm:tw-grid-cols-1 md:tw-grid-cols-2" v-if="campaigns && campaigns.length > 0">
			<Card
				v-for="campaign in campaigns"
				:key="campaign.id"
				:legend-color="programColor(campaign.program)"
				class="tw-mb-4"
			>
				<template #legend>
					{{ campaign.program.label }}
				</template>

				<template #title>
					{{ campaign.label }}
				</template>

				<template #information_1>
					<div class="tw-flex tw-items-center tw-gap-1">
						<span class="material-symbols-outlined tw-text-neutral-600" aria-hidden="true">schedule</span>
						<p class="tw-text-neutral-600" v-if="campaign.status === 'closed'">
							{{ translate('COM_EMUNDUS_CAMPAIGNS_CLOSED_ON') }} {{ formatDateForCampaign(campaign.end_date.date) }}
						</p>
						<p class="tw-text-neutral-600" v-else-if="campaign.status === 'upcoming'">
							{{ translate('COM_EMUNDUS_CAMPAIGNS_OPENING_ON') }} {{ formatDateForCampaign(campaign.start_date.date) }}
						</p>
						<p class="tw-text-neutral-600" v-else>
							{{ translate('COM_EMUNDUS_CAMPAIGNS_CLOSE_DATE') }} : {{ formatDateForCampaign(campaign.end_date.date) }}
						</p>
					</div>
					<div class="tw-flex tw-items-center tw-gap-1">
						<span class="material-symbols-outlined tw-text-neutral-600" aria-hidden="true">public</span>
						<p class="tw-text-neutral-600">
							{{ campaign.timezone }}
						</p>
					</div>
				</template>

				<template #description>
					<div
						v-html="campaign.short_description ? campaign.short_description : truncateText(campaign.description, 100)"
					></div>
					<div v-if="campaign.moreProperties && campaign.moreProperties.id && campaign.moreProperties.id.value > 0">
						<ul
							v-for="property in filterProperties(campaign.moreProperties)"
							:key="property.id"
							class="tw-list-none tw-pl-0"
						>
							<li>
								<strong>{{ property.label }}</strong> : {{ property.formatted_value }}
							</li>
						</ul>
					</div>
				</template>

				<template #actions>
					<div class="tw-flex tw-flex-col tw-gap-2">
						<Button
							variant="secondary"
							width="full"
							icon="open_in_new"
							icon-position="right"
							@click="openCampaignDetails(campaign.id, this.campaigns)"
						>
							{{ translate('COM_EMUNDUS_CAMPAIGNS_MORE_DETAILS') }}
						</Button>
						<Button
							v-if="campaign.status === 'open'"
							variant="primary"
							width="full"
							@click="applyToCampaign(campaign.id)"
						>
							{{ translate(applyText) }}
						</Button>
						<Button v-else-if="campaign.status === 'closed'" variant="disabled" width="full" :disabled>
							{{ translate('COM_EMUNDUS_CAMPAIGNS_CLOSED') }}
						</Button>
						<Button v-else-if="campaign.status === 'upcoming'" variant="disabled" width="full" :disabled>
							{{ translate('COM_EMUNDUS_CAMPAIGNS_UPCOMING') }}
						</Button>
						<Button v-else-if="campaign.status === 'selected'" variant="disabled" width="full" :disabled>
							{{ translate('COM_EMUNDUS_CAMPAIGNS_SELECTED') }}
						</Button>
					</div>
				</template>
			</Card>
		</div>
		<NoResults v-else message="COM_EMUNDUS_CAMPAIGNS_NO_RESULTS_FOUND" />
	</div>
</template>

<style scoped></style>
