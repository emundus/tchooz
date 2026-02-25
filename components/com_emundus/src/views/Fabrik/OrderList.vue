<script>
import { VueDraggableNext } from 'vue-draggable-next';

export default {
	name: 'OrderList',
	props: {
		options: {
			type: Array,
			default: () => [],
		},
		elementId: {
			type: String,
			default: '',
		},
		elementName: {
			type: String,
			default: '',
		},
		value: {
			type: String,
			default: '',
		},
	},
	components: {
		draggable: VueDraggableNext,
	},
	data() {
		return {
			orderedList: [],
		};
	},
	created() {
		this.orderedList = this.options;

		if (this.value) {
			const valueArray = this.value.split(',');
			valueArray.map((value) => value.replace(/"/g, ''));
			this.orderedList.sort((a, b) => {
				return valueArray.indexOf(a.value) - valueArray.indexOf(b.value);
			});
		}
	},
	methods: {
		onDragEnd() {
			window.postMessage({ type: 'orderListUpdated', elementId: this.elementId, value: this.orderedValues }, '*');
			console.log('Drag ended. Updated order:', this.orderedValues);
		},
	},
	computed: {
		orderedValues() {
			return this.orderedList.map((option) => '"' + option.value + '"').join(',');
		},
	},
};
</script>

<template>
	<div class="order-list-field">
		<draggable v-model="orderedList" animation="200" handle=".draggable-handle" @dragend="onDragEnd">
			<div
				v-for="(option, index) in orderedList"
				:key="option.value"
				class="draggable-handle tw-mb-2 tw-flex tw-w-full tw-items-center tw-gap-2"
			>
				<span>{{ index + 1 }}.</span>
				<div class="tw-flex tw-items-center tw-gap-2 tw-rounded tw-border tw-p-2">
					<span class="material-symbols-outlined tw-cursor-grab">drag_indicator</span>
					<span>{{ option.label }}</span>
				</div>
			</div>
		</draggable>

		<input
			type="text"
			class="hidden fabrikinput form-control inputbox text"
			:id="elementId"
			:name="elementName"
			:value="orderedValues"
		/>
	</div>
</template>

<style scoped></style>
