<script>
import Popover from '@/components/Popover.vue';
import Modal from '@/components/Modal.vue';
import alert from '@/mixins/alerts.js';

export default {
	name: 'RegisteredFilters',
	components: { Popover, Modal },
	mixins: [alert],
	emits: ['toggle-opened', 'select', 'toggle-favorite', 'on-rename', 'update', 'share', 'delete', 'save-new'],
	props: {
		filters: Array,
		selectedFilter: [Number, String],
		opened: Boolean,
		canShareFilters: Boolean,
		hasUnsaved: Boolean,
	},
	data() {
		return {
			filterToRename: null,
			previousFilterName: '',
		};
	},
	methods: {
		onClickUpdate(filter) {
			this.alertConfirm(
				'MOD_EMUNDUS_FILTERS_CONFIRM_UPDATE_FILTER',
				this.translate('MOD_EMUNDUS_FILTERS_CONFIRM_UPDATE_FILTER_TEXT') + filter.name,
				'MOD_EMUNDUS_FILTERS_CONFIRM_UPDATE_FILTER_CONFIRM',
			).then((result) => {
				if (result.isConfirmed) {
					this.$emit('update', filter.id);
				}
			});
		},
	},
};
</script>

<template>
	<div id="registered-filters-wrapper">
		<label
			for="registered-filters"
			class="tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-gap-1"
			@click="$emit('toggle-opened')"
		>
			<span class="material-symbols-outlined" v-if="opened">expand_more</span>
			<span class="material-symbols-outlined" v-else>chevron_right</span>
			<span>{{ translate('MOD_EMUNDUS_FILTERS_SAVED_FILTERS') }} ({{ filters.length }})</span>
		</label>

		<div v-if="opened">
			<div class="tw-my-4">
				<p v-if="filters.length < 1">{{ translate('MOD_EMUNDUS_FILTERS_NO_SAVED_FILTERS') }}</p>

				<ul
					v-else
					class="tw-max-h-[150px] tw-list-none tw-overflow-y-auto tw-rounded-coordinator-form tw-border tw-bg-white !tw-pl-0"
				>
					<li
						v-for="filter in filters"
						:key="filter.id"
						:class="{ 'active tw-text-main-500': selectedFilter === filter.id }"
						class="tw-flex tw-cursor-pointer tw-items-center tw-justify-between tw-border-b tw-p-2 last:tw-border-b-0"
					>
						<!-- Normal filters -->
						<div v-if="filter.id !== 'tmp'" class="tw-flex tw-w-full tw-flex-row tw-items-center tw-justify-between">
							<div class="tw-flex tw-w-full tw-flex-row tw-items-center tw-gap-2" @click="$emit('select', filter.id)">
								<span
									v-if="filter.favorite"
									class="material-icons-outlined favorite"
									@click="$emit('toggle-favorite', filter.id, 0)"
									>star</span
								>
								<span v-else class="material-symbols-outlined" @click="$emit('toggle-favorite', filter.id, 1)"
									>star</span
								>
								<div class="tw-ml-2 tw-w-full">
									<span>{{ filter.name }}</span>
								</div>
							</div>

							<popover :position="'bottom'" :ref="'popover' + filter.id" :absolute="true">
								<div class="popover-content">
									<ul class="em-text-color tw-list-none tw-p-3">
										<li
											class="not-to-close-modal tw-flex tw-cursor-pointer tw-flex-row tw-items-center tw-rounded-coordinator-form tw-p-2 hover:tw-bg-neutral-300"
											@click="$emit('on-rename', filter.id)"
										>
											<span class="material-symbols-outlined tw-mr-2">edit</span>
											<span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_RENAME') }}</span>
										</li>
										<li
											class="tw-flex tw-cursor-pointer tw-flex-row tw-items-center tw-rounded-coordinator-form tw-p-2 hover:tw-bg-neutral-300"
											@click="onClickUpdate(filter)"
										>
											<span class="material-symbols-outlined tw-mr-2">refresh</span>
											<span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_UPDATE') }}</span>
										</li>
										<li
											v-if="canShareFilters"
											class="tw-flex tw-cursor-pointer tw-flex-row tw-items-center tw-rounded-coordinator-form tw-p-2 hover:tw-bg-neutral-300"
											@click="$emit('share', filter.id)"
										>
											<span class="material-symbols-outlined tw-mr-2">share</span>
											<span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_SHARE') }}</span>
										</li>
										<!--<li
											class="tw-flex tw-cursor-pointer tw-flex-row tw-items-center tw-p-2"
											@click="$emit('define-default', filter.id)"
										>
											<span class="material-symbols-outlined tw-mr-2">check_box</span>
											<span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_DEFINE_AS_DEFAULT') }}</span>
										</li>-->
										<li
											class="tw-flex tw-cursor-pointer tw-flex-row tw-items-center tw-rounded-coordinator-form tw-p-2 hover:tw-bg-neutral-300"
											@click="$emit('delete', filter.id)"
										>
											<span class="material-symbols-outlined tw-mr-2 tw-text-red-500">delete</span>
											<span class="tw-text-red-500">
												{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_DELETE') }}
											</span>
										</li>
									</ul>
								</div>
							</popover>
						</div>
					</li>
				</ul>
			</div>

			<button
				class="tw-btn-primary tw-w-full tw-text-white hover:tw-text-main-500"
				:class="{ 'tw-disabled': hasUnsaved }"
				@click="$emit('save-new')"
			>
				<span class="material-symbols-outlined tw-mr-4 tw-text-inherit">save</span>
				<span>{{ translate('MOD_EMUNDUS_FILTERS_SAVE_FILTERS') }}</span>
			</button>
		</div>
	</div>
</template>
