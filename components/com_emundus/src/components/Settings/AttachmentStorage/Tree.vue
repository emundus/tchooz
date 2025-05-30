<template>
	<div>
		<div class="tw-mb-4 tw-flex tw-items-center" :class="'em-level-' + node.level">
			<span class="material-symbols-outlined" v-if="node.type !== 0">folder</span>
			<span class="material-symbols-outlined" v-else>folder</span>

			<select
				v-if="(other_tags.includes(node.type) || node.type === 0) && node.type !== ''"
				class="em-clear-dropdown tree-branch tw-ml-2 tw-mr-2"
				v-model="node.type"
			>
				<option value="0" selected>
					/{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_SELECT_TYPE') }}
				</option>
				<option :value="field.value" v-for="field in fieldsData">
					{{ translate(field.label) }}
				</option>
			</select>
			<input
				v-else
				type="text"
				class="em-xs-input tree-branch tw-ml-2 tw-mr-2 tw-w-auto"
				:value="node.type"
				@focusout="updateNodeType($event)"
			/>

			<v-popover :popoverArrowClass="'custom-popover-arrow'">
				<span class="tooltip-target b3 material-symbols-outlined">more_horiz</span>
				<template slot="popover">
					<div
						v-if="(!other_tags.includes(node.type) && node.type !== 0) || node.type === ''"
						class="em-hover-background-neutral-300 tw-cursor-pointer tw-px-2 tw-py-3 tw-text-sm"
						@click="node.type = 0"
					>
						{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GO_BACK_TO_SELECT') }}
					</div>
					<div
						class="em-hover-background-neutral-300 tw-cursor-pointer tw-px-2 tw-py-3 tw-text-sm"
						v-if="node.level < level_max"
						@click="$emit('addNode', node)"
					>
						{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_ADD_MENU') }}
					</div>
					<div
						class="em-hover-background-neutral-300 tw-cursor-pointer tw-px-2 tw-py-3 tw-text-sm tw-text-red-600"
						@click="$emit('deleteNode', node)"
					>
						{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_DELETE') }}
					</div>
				</template>
			</v-popover>
		</div>

		<div v-for="children in node.childrens" class="tw-flex tw-items-center" :class="'em-level-' + children.level">
			<Tree
				:node="children"
				@addNode="addNode"
				@deleteNode="deleteNode"
				@saveConfig="$emit('saveConfig')"
				:level_max="level_max"
				:emundus_tags="emundus_tags"
			/>
		</div>
	</div>
</template>

<script>
import fields from '../../../data/ged/fieldsType';

export default {
	name: 'Tree',
	props: {
		node: {
			type: Object,
			required: true,
		},
		level_max: {
			type: Number,
			default: 3,
		},
		emundus_tags: Array,
	},
	data() {
		return {
			fieldsData: [],
			other_tags: [],
		};
	},
	mounted() {
		this.fieldsData = fields['default'];
		this.fieldsData.forEach((field) => {
			this.other_tags.push(field.value);
		});
	},
	methods: {
		addNode(node) {
			this.$emit('addNode', node);
		},
		deleteNode(node) {
			this.$emit('deleteNode', node);
		},
		updateNodeType(event) {
			this.node.type = event.target.value;
		},
	},

	watch: {
		'node.type': function () {
			this.$emit('saveConfig');
		},
	},
};
</script>

<style scoped>
.em-level-1 {
	margin-left: 16px;
}

.em-level-2 {
	margin-left: 24px;
}

.em-level-3 {
	margin-left: 32px;
}

.em-level-4 {
	margin-left: 40px;
}

.em-level-5 {
	margin-left: 48px;
}

.em-clear-dropdown {
	border: unset;
	height: auto;
}

.em-clear-dropdown:focus {
	outline: unset;
	background: #e3e5e8;
}

.em-xs-input {
	height: 25px;
	border-width: 0;
	width: auto;
}

.em-xs-input:focus {
	border-width: 1px;
}

.tree-branch {
	margin-bottom: 0;
}
</style>
