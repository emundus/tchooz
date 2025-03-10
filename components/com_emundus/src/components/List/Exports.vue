<script>
import Popover from '@/components/Popover.vue';

export default {
	name: 'Exports',
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

		onClickExport(exp) {
			this.$emit('exp', exp);
		},
	},
	computed: {
		multipleExportsPopover() {
			let exports = [];

			if (this.checkedItems.length > 0) {
				exports = this.tab.exports.filter((exp) => {
					return exp.multiple;
				});
			}

			return exports;
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
	<section id="default-exports" class="tw-flex tw-gap-4">
		<div class="tw-flex tw-items-center tw-gap-2">
			<popover
				v-if="checkedItems.length > 0 && multipleExportsPopover.length > 0"
				:button="translate('EXPORT')"
				:button-class="'tw-bg-white tw-border tw-h-[38px] hover:tw-border-form-border-hover tw-rounded-form'"
				:icon="'keyboard_arrow_down'"
				:position="'bottom-left'"
				:popoverContentStyle="{ width: 'max-content' }"
				class="custom-popover-arrow"
			>
				<ul class="tw-m-0 tw-list-none tw-items-center tw-p-4">
					<li
						v-for="exp in multipleExportsPopover"
						:key="exp.name"
						@click="onClickExport(exp)"
						class="tw-px-2 tw-py-1.5"
						:class="{
							'tw-cursor-not-allowed tw-text-neutral-500': !(
								typeof exp.showon === 'undefined' || evaluateShowOn(exp.showon)
							),
							'tw-cursor-pointer tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300':
								(typeof exp.showon !== 'undefined' && evaluateShowOn(exp.showon)) || typeof exp.showon === 'undefined',
						}"
					>
						{{ translate(exp.label) }}
					</li>
				</ul>
			</popover>
		</div>
	</section>
</template>

<style scoped></style>
