<template>
	<div id="form-builder-document-formats" class="!tw-pr-4">
		<p id="form-builder-document-title" class="tw-text-center tw-full tw-p-4">
			{{ translate('COM_EMUNDUS_FORM_BUILDER_FORMATS') }}
		</p>
		<input
			v-if="formats.length > 0"
			id="search"
			v-model="search"
			type="text"
			class="tw-mt-4 tw-full"
			:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_SEARCH_FORMAT')"
		/>
		<draggable
			v-model="displayedFormats"
			class="draggables-list"
			:group="{ name: 'form-builder-documents', pull: 'clone', put: false }"
			:sort="false"
			:clone="setCloneFormat"
			@start="$emit('dragging-element')"
			@end="onDragEnd"
		>
			<transition-group>
				<div
					v-for="format in displayedFormats"
					:key="format.id"
					class="tw-flex tw-justify-between tw-items-center draggable-element tw-mt-2 tw-mb-2 tw-p-4"
				>
					<span id="format-name" class="tw-full tw-p-4" :title="format.name[shortDefaultLang]">{{
						format.name[shortDefaultLang]
					}}</span>
					<span class="material-symbols-outlined"> drag_indicator </span>
				</div>
			</transition-group>
		</draggable>
	</div>
</template>

<script>
import { VueDraggableNext } from 'vue-draggable-next';
import formBuilderMixin from '../../mixins/formbuilder';
import formService from '@/services/form';

export default {
	components: {
		draggable: VueDraggableNext,
	},
	props: {
		profile_id: {
			type: Number,
			required: true,
		},
	},
	mixins: [formBuilderMixin],
	data() {
		return {
			formats: [],
			cloneFormat: null,
			search: '',
		};
	},
	created() {
		this.getFormats();
	},
	methods: {
		getFormats() {
			formService.getDocumentModels().then((response) => {
				if (response.status) {
					this.formats = response.data;
				}
			});
		},
		setCloneFormat(format) {
			this.cloneFormat = format;
		},
		onDragEnd(event) {
			const to = event.to;
			if (to === null || to.id === '') {
				return;
			}

			this.cloneFormat.mandatory = to.id == 'required-documents' ? '1' : '0';
			this.$emit('open-create-document', this.cloneFormat);
		},
	},
	computed: {
		displayedFormats() {
			return this.formats.filter((format) => {
				return this.search.length > 0 && this.formats.length > 0
					? format.name[this.shortDefaultLang].toLowerCase().includes(this.search.toLowerCase())
					: true;
			});
		},
	},
};
</script>

<style lang="scss">
#form-builder-document-formats {
	#form-builder-document-title {
		border-bottom: 1px solid black;
	}

	.draggable-element {
		width: 258px;
		height: 48px;
		font-size: 14px;
		background-color: #fafafa;
		border: 1px solid #f2f2f3;
		cursor: grab;
	}

	#format-name {
		white-space: nowrap;
		max-width: 100%;
		text-overflow: ellipsis;
		overflow: hidden;
	}
}
</style>
