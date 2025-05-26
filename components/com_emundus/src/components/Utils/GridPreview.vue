<script>
export default {
	name: 'GridPreview',
	props: {
		columns: {
			type: Array,
			required: true,
		},
		rows: {
			type: Array,
			required: true,
		},
		currentPosition: {
			type: String,
			default: 'A1',
		},
		displayLegend: {
			type: Boolean,
			default: false,
		},
		image: {
			type: String,
			default: '/media/com_emundus/images/document_mockup.png',
		},
	},
	emits: ['updatePosition'],
	methods: {
		updatePosition(position) {
			this.$emit('updatePosition', position);
		},
	},
	computed: {
		// Computed properties can be added here if needed
		backgroundImage() {
			return this.image ?? `/media/com_emundus/images/document_mockup.png`;
		},
	},
};
</script>

<template>
	<div
		class="tw-bg-no-repeat"
		:class="[displayLegend ? 'tw-aspect-[210/240]' : 'tw-aspect-[210/297]']"
		:style="[
			'background-image: url(' + this.backgroundImage + ')',
			displayLegend ? 'background-size: 65%' : 'background-size: contain',
			displayLegend ? 'background-position: 85% bottom' : 'background-position: center',
		]"
	>
		<!-- COLUMNS LETTERS -->
		<div v-if="displayLegend" class="tw-grid" :class="'tw-grid-cols-' + (columns.length + 1)">
			<div></div>
			<div v-for="(column, index) in columns" :key="index" class="tw-p-2 tw-text-center tw-text-neutral-500">
				{{ column }}
			</div>
		</div>

		<!-- ROWS -->
		<div class="tw-grid tw-rounded-[10px] tw-border tw-border-neutral-500" :class="'tw-grid-rows-' + rows.length">
			<div
				v-for="(row, rowIndex) in rows"
				:key="rowIndex"
				:class="[
					displayLegend ? 'tw-grid-cols-' + (columns.length + 1) : 'tw-grid-cols-' + columns.length,
					'tw-grid',
					'tw-items-center',
					'tw-text-end',
				]"
			>
				<span v-if="displayLegend" class="tw-mr-4 tw-text-neutral-500">{{ row }}</span>
				<div
					v-for="(column, index) in columns"
					@click="updatePosition(column + (rowIndex + 1))"
					:key="index"
					class="tw-cursor-pointer tw-border tw-border-neutral-500 tw-p-2 tw-text-center"
					style="height: 5rem"
					:class="[
						index === 0 && rowIndex === 0 ? 'tw-rounded-ss-coordinator' : '',
						index === columns.length - 1 && rowIndex === rows.length - 1 ? 'tw-rounded-ee-coordinator' : '',
						index === columns.length - 1 && rowIndex === 0 ? 'tw-rounded-se-coordinator' : '',
						index === 0 && rowIndex === rows.length - 1 ? 'tw-rounded-es-coordinator' : '',
						currentPosition === column + (rowIndex + 1) ? 'tw-bg-profile-light' : '',
					]"
				></div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
