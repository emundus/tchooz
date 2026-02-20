<template>
	<div :class="'step-types-level-' + parentId">
		<div v-for="type in stepTypesByParentId" :key="type.id">
			<div class="tw-mb-2 tw-flex tw-w-full tw-flex-row tw-items-center">
				<span v-for="i in level" :key="i" class="material-symbols-outlined">horizontal_rule</span>
				<input :id="'type-' + type.id + '-label'" :name="'type-' + type.id + '-label'" v-model="type.label" />
				<span v-if="!type.system" class="material-symbols-outlined tw-cursor-pointer" @click="deleteType(type.id)">
					delete
				</span>
				<color-picker
					class="tw-ml-4"
					v-model="type.class"
					:swatches="swatches"
					:id="'step_swatches_' + type.id"
					:row-length="8"
					:position="'left'"
					@input="updateTypeColor(type)"
				>
				</color-picker>
			</div>
			<div>
				<StepTypesByLevel
					@updateTypes="onUpdateTypes"
					v-if="stepTypesOfParentId(type.id).length > 0"
					:defaultTypes="types"
					:parentId="type.id"
					:level="level + 1"
				></StepTypesByLevel>
			</div>
			<div
				class="tw-flex tw-w-full tw-flex-row tw-items-center"
				v-if="level < levelMax && (type.code === 'evaluator' || type.code === 'payment')"
			>
				<button @click="addChildrenStepType(type)" class="tw-btn-secondary tw-mb-2 tw-mt-2">
					{{ translate('COM_EMUNDUS_WORKFLOW_ADD_CHILDREN_STEP_TYPE') }}
				</button>
			</div>
		</div>
		<div v-if="level === 0" class="tw-flex tw-flex-row tw-justify-end">
			<button @click="saveStepTypes" class="tw-btn-primary">
				{{ translate('SAVE') }}
			</button>
		</div>
	</div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import ColorPicker from '@/components/ColorPicker.vue';
import tailwindPreset from '@/assets/data/colorpicker/presets/tailwind.js';

export default {
	name: 'StepTypesByLevel',
	components: { ColorPicker },
	props: {
		defaultTypes: {
			type: Array,
			required: true,
		},
		parentId: {
			type: Number,
			default: 0,
		},
		level: {
			type: Number,
			default: 0,
		},
		levelMax: {
			type: Number,
			default: 1,
		},
	},
	data() {
		return {
			types: [],
			colors: [],
			swatches: [],
		};
	},
	created() {
		let root = document.querySelector(':root');
		this.variables = getComputedStyle(root);

		for (const swatch of tailwindPreset) {
			let color = this.variables.getPropertyValue('--' + swatch + '-500');
			this.colors.push({ name: swatch, value: color });
			this.swatches.push(color);
		}
	},
	mounted() {
		this.types = this.defaultTypes;
		this.types.forEach((type) => {
			let index = this.colors.findIndex((item) => item.name === type.class);
			if (index !== -1) {
				type.class = this.colors[index].value;
			}
		});
	},
	methods: {
		addStepType() {
			this.types.push({
				id: this.lastId + 1,
				label: 'Nouveau type',
				parent_id: this.parentId,
			});
			this.$emit('updateTypes', this.types);
		},
		deleteType(id) {
			this.types = this.types.filter((type) => type.id !== id);
			this.$emit('updateTypes', this.types);
		},
		addChildrenStepType(type) {
			this.types.push({
				id: this.lastId + 1,
				label: 'Nouveau type',
				parent_id: type.id,
			});
			this.$emit('updateTypes', this.types);
		},
		stepTypesOfParentId(parentId) {
			return this.types.filter((type) => type.parent_id === parentId);
		},
		saveStepTypes() {
			workflowService
				.saveTypes(
					this.types.map((type) => {
						let index = this.colors.findIndex((item) => item.value === type.class);
						if (index !== -1) {
							type.class = this.colors[index].name;
						}

						return type;
					}),
				)
				.then((response) => {
					if (response.status) {
						Swal.fire({
							icon: 'success',
							title: this.translate('COM_EMUNDUS_WORKFLOW_SAVE_STEP_TYPES_SUCCESS'),
							showConfirmButton: false,
							timer: 1500,
						});
					}

					this.types.forEach((type) => {
						let index = this.colors.findIndex((item) => item.name === type.class);
						if (index !== -1) {
							type.class = this.colors[index].value;
						}
					});
				})
				.catch((error) => {
					console.log(error);
				});
		},
		onUpdateTypes(types) {
			this.types = types;
			this.$emit('updateTypes', this.types);
		},
	},
	computed: {
		stepTypesByParentId() {
			return this.types.filter((type) => type.parent_id === this.parentId);
		},
		lastId() {
			return this.types.reduce((acc, type) => {
				return type.id > acc ? type.id : acc;
			}, 0);
		},
	},
};
</script>

<style scoped></style>
