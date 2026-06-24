<script>
import Popover from '@/components/Popover.vue';

export default {
	name: 'Imports',
	components: {
		Popover,
	},
	props: {
		items: {
			type: Object,
			default: () => {},
		},
		checkedItems: {
			type: Array,
			default: () => [],
		},
		views: {
			type: Object,
			default: () => {},
		},
		tab: {
			type: Object,
			default: () => {},
		},
		tabKey: {
			type: Number,
			default: '',
		},

		// V-Model
		view: {
			type: String,
			default: 'table',
		},
		searches: {
			type: Object,
			default: () => {},
		},
	},
	emits: ['update:view', 'update:searches'],
	data() {
		return {
			currentView: this.view,
			currentSearches: this.searches,
		};
	},
	methods: {
		evaluateShowOn(showon = null) {
			if (showon === null) {
				return false;
			}

			let items = this.checkedItems;

			let show = [];

			items.forEach((item) => {
				// If item is an id, we get the item from the list
				if (typeof item === 'number') {
					item = this.items[this.tabKey].find((i) => i.id === item);
				}
				switch (showon.operator) {
					case '==':
					case '=':
						show.push(item[showon.key] == showon.value);
						break;
					case '!=':
						show.push(item[showon.key] != showon.value);
						break;
					case '>':
						show.push(item[showon.key] > showon.value);
						break;
					case '<':
						show.push(item[showon.key] < showon.value);
						break;
					case '>=':
						show.push(item[showon.key] >= showon.value);
						break;
					case '<=':
						show.push(item[showon.key] <= showon.value);
						break;
					default:
						show.push(true);
				}
			});

			// Return true if all items match the condition
			return show.every((s) => s === true);
		},

		onClickImport(imp) {
			this.$emit('imp', imp);
		},
	},
	computed: {
		displayedImports() {
			return this.tab.imports || [];
		},
	},
	watch: {
		currentView() {
			this.$emit('update:view', this.currentView);
		},
		currentSearches() {
			this.$emit('update:searches', this.currentSearches);
		},
	},
};
</script>

<template>
	<section id="default-imports" class="tw-flex tw-gap-4">
		<div v-if="displayedImports.length > 0">
			<label class="!tw-mb-0 tw-font-medium tw-opacity-0">{{ translate('COM_EMUNDUS_ONBOARD_IMPORT') }}</label>
			<popover
				:button="translate('COM_EMUNDUS_ONBOARD_IMPORT')"
				:button-class="'tw-btn-secondary tw-h-form'"
				:icon="'keyboard_arrow_down'"
				:position="'bottom-left'"
				:popoverContentStyle="{ width: 'max-content' }"
				class="custom-popover-arrow"
			>
				<ul class="tw-m-0 tw-list-none tw-items-center tw-p-4">
					<li
						v-for="imp in displayedImports"
						:key="imp.name"
						@click="onClickImport(imp)"
						class="tw-px-2 tw-py-1.5"
						:class="{
							'tw-cursor-not-allowed tw-text-neutral-500': !(
								typeof imp.showon === 'undefined' || evaluateShowOn(imp.showon)
							),
							'tw-cursor-pointer tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300':
								(typeof imp.showon !== 'undefined' && evaluateShowOn(imp.showon)) || typeof imp.showon === 'undefined',
						}"
					>
						{{ translate(imp.label) }}
					</li>
				</ul>
			</popover>
		</div>
	</section>
</template>

<style scoped></style>
