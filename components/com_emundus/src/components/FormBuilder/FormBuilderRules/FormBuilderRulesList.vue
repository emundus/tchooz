<template>
	<div id="form-builder-rules-list" style="min-width: 260px">
		<div class="tw-mt-2">
			<input
				v-model="keywords"
				type="text"
				class="formbuilder-searchbar"
				:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_SEARCH_RULE')"
			/>
			<div v-for="rule in publishedRules" :key="rule.id" class="draggables-list" @click="$emit('add-rule', rule.value)">
				<div class="form-builder-element tw-flex tw-cursor-pointer tw-items-center tw-justify-between tw-gap-3 tw-p-3">
					<span class="material-symbols-outlined">{{ rule.icon }}</span>
					<span class="tw-w-full">{{ translate(rule.name) }}</span>
					<span class="material-symbols-outlined">add_circle_outline</span>
				</div>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
// external libraries
//import draggable from 'vuedraggable';
import { VueDraggableNext } from 'vue-draggable-next';

import formBuilderMixin from '../../../mixins/formbuilder';
import errorsMixin from '../../../mixins/errors';

import rulesData from '../../../../data/form-builder/form-builder-rules.json';

export default {
	components: {
		draggable: VueDraggableNext,
	},
	mixins: [formBuilderMixin, errorsMixin],
	props: {
		form: {
			type: Object,
			required: false,
		},
	},
	data() {
		return {
			rules: [],
			loading: false,
			keywords: '',
		};
	},
	created() {
		this.rules = this.getRules();
	},
	methods: {
		getRules() {
			return rulesData;
		},

		addRule(rule) {
			this.loading = true;
		},
	},
	computed: {
		publishedRules() {
			if (this.keywords) {
				return this.rules.filter(
					(rule) => rule.published && this.translate(rule.name).toLowerCase().includes(this.keywords.toLowerCase()),
				);
			} else {
				return this.rules.filter((rule) => rule.published);
			}
		},
	},
};
</script>

<style lang="scss">
.form-builder-element {
	width: 258px;
	height: auto;
	font-size: 14px;
	margin: 8px 0px;
	background-color: #fafafa;
	border: 1px solid #f2f2f3;
	cursor: grab;
	border-radius: calc(var(--em-default-br) / 2);
	&:hover {
		background-color: var(--neutral-200);
	}
}
#form-builder-elements input.formbuilder-searchbar,
#form-builder-document-formats input.formbuilder-searchbar {
	border-width: 0 0 1px 0;
	border-radius: 0;
	border-color: var(--neutral-400);
	&:focus {
		outline: unset;
		border-bottom-color: var(--em-form-outline-color-focus);
	}
}
</style>
