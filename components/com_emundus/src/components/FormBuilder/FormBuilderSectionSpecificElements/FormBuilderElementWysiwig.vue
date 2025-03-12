<template>
	<div id="form-builder-wysiwig">
		<div v-if="loading" class="em-loader"></div>
		<div v-else>
			<div v-show="!editable" v-html="element.element" :id="element.id" @click="editable = true"></div>
			<transition :name="'slide-down'" type="transition">
				<tip-tap-editor
					:id="'editor_' + element.id"
					v-model="element.default"
					:editor-content-height="'30em'"
					:class="'tw-mt-1'"
					:locale="'fr'"
					:preset="'custom'"
					:plugins="editorPlugins"
					:toolbar-classes="['tw-bg-white']"
					:editor-content-classes="['tw-bg-white']"
				/>
			</transition>
		</div>
	</div>
</template>

<script>
import formBuilderService from '@/services/formbuilder.js';

import TipTapEditor from 'tip-tap-editor';
import 'tip-tap-editor/style.css';
import '../../../../../../templates/g5_helium/css/editor.css';

export default {
	props: {
		element: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
	},
	components: {
		TipTapEditor,
	},
	data() {
		return {
			loading: false,

			editable: false,
			dynamicComponent: 0,

			editorPlugins: ['history', 'link', 'bold', 'italic', 'underline', 'left', 'center', 'right', 'h1', 'h2', 'ul'],
		};
	},
	created() {},
	methods: {
		updateDisplayText(value) {
			this.editable = false;
			formBuilderService.updateDefaultValue(this.$props.element.id, value).then((response) => {
				this.$emit('update-element');
			});
		},
	},
	watch: {},
};
</script>
