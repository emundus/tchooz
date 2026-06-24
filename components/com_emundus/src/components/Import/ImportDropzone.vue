<script>
export default {
	name: 'ImportDropzone',
	emits: ['file-selected', 'show-help', 'remove-file'],
	props: {
		uploading: {
			type: Boolean,
			default: false,
		},
		uploadError: {
			type: String,
			default: null,
		},
		entityTerm: {
			type: String,
			default: '',
		},
		supportedFormats: {
			type: Array,
			default: () => ['csv', 'xlsx', 'xls', 'json'],
		},
		selectedFile: {
			type: Object,
			default: null,
		},
	},
	data() {
		return {
			dragOver: false,
		};
	},
	computed: {
		acceptedExtensions() {
			return this.supportedFormats.map((f) => '.' + f).join(',');
		},
		formatsLabel() {
			return this.supportedFormats.join(', ');
		},
	},
	methods: {
		onDragOver(e) {
			e.preventDefault();
			this.dragOver = true;
		},
		onDragLeave() {
			this.dragOver = false;
		},
		onDrop(e) {
			e.preventDefault();
			this.dragOver = false;
			const file = e.dataTransfer.files[0];
			if (file) this.$emit('file-selected', file);
		},
		onFileInputChange(e) {
			const file = e.target.files[0];
			if (file) this.$emit('file-selected', file);
			e.target.value = '';
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-1 tw-flex-col tw-gap-4">
		<p class="tw-m-0 tw-text-center">
			{{ translate('COM_EMUNDUS_IMPORT_FIELDS_INTRO').replace('%s', entityTerm) }}
		</p>

		<button
			type="button"
			class="tw-btn tw-btn-tertiary tw-mx-auto tw-inline-flex tw-w-fit tw-cursor-pointer tw-items-center tw-gap-1"
			@click="$emit('show-help')"
		>
			<span class="material-symbols-outlined">manage_search</span>
			{{ translate('COM_EMUNDUS_IMPORT_VIEW_ACCEPTED_COLUMNS') }}
		</button>

		<div class="tw-text-sm">
			<p class="tw-m-0">{{ translate('COM_EMUNDUS_IMPORT_ACCEPTED_FORMATS') }} {{ formatsLabel }}</p>
		</div>

		<input ref="fileInput" type="file" style="display: none" :accept="acceptedExtensions" @change="onFileInputChange" />

		<div
			v-if="selectedFile === null"
			class="tw-flex tw-min-h-[10rem] tw-w-full tw-flex-1 tw-cursor-pointer tw-flex-col tw-items-center tw-justify-center tw-gap-2 tw-rounded-coordinator tw-border-2 tw-border-dashed tw-border-neutral-500 tw-py-8"
			:class="dragOver ? 'tw-bg-neutral-100' : ''"
			@click="$refs.fileInput.click()"
			@dragover="onDragOver"
			@dragleave="onDragLeave"
			@drop="onDrop"
		>
			<template v-if="!uploading">
				<span class="material-symbols-outlined tw-text-4xl">drive_folder_upload</span>
				<p class="tw-m-0 tw-font-medium">{{ translate('COM_EMUNDUS_IMPORT_DROP_FILE_HERE') }}</p>
			</template>

			<div v-else class="tw-flex tw-items-center tw-gap-2">
				<span class="material-symbols-outlined tw-animate-spin">progress_activity</span>
				{{ translate('COM_EMUNDUS_IMPORT_ANALYZING') }}
			</div>
		</div>

		<div v-else class="tw-flex tw-items-center tw-gap-4">
			<span class="material-symbols-outlined tw-text-4xl">insert_drive_file</span>
			<div>
				<p class="tw-m-0 tw-font-medium">{{ selectedFile.name }}</p>
				<p class="tw-m-0 tw-text-sm tw-text-profile-full">{{ translate('COM_EMUNDUS_IMPORT_FILE_READY') }}</p>
			</div>
			<button class="tw-ml-auto tw-bg-transparent" @click="$emit('remove-file')">
				<span class="material-symbols-outlined tw-text-red-500">close</span>
			</button>
		</div>

		<div
			v-if="uploadError"
			class="tw-flex tw-items-start tw-gap-2 tw-rounded-coordinator-form tw-bg-red-50 tw-p-3 tw-text-sm tw-text-red-700"
		>
			<span class="material-symbols-outlined tw-text-red-500">error</span>
			{{ uploadError }}
		</div>
	</div>
</template>

<style scoped></style>
