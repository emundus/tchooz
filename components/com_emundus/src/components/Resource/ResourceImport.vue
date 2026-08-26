<script>
import { Fileupload, Button } from '@emundus/ui';

import ModalHeader from '@/components/Utils/Modal/Header.vue';

import resourceService from '@/services/resource.js';

import alerts from '@/mixins/alerts.js';

// Mirror of ResourceService::MAX_FILESIZE_MB (backend source of truth). Keep in sync.
const MAX_FILESIZE_MB = 20;
const MAX_FILESIZE_BYTES = MAX_FILESIZE_MB * 1024 * 1024;

export default {
	name: 'ResourceImport',
	components: { ModalHeader, Fileupload, Button },
	mixins: [alerts],
	props: {
		folderId: {
			type: Number,
			default: null,
		},
	},
	emits: ['close', 'update-items'],
	data() {
		return {
			files: [],
			isRunning: false,
			folderOptions: [],
			// Default the destination to the folder currently being browsed.
			targetFolderId: this.folderId !== null ? String(this.folderId) : '',
		};
	},
	computed: {
		hasFiles() {
			return this.files.length > 0;
		},
	},
	async mounted() {
		this.folderOptions = await resourceService.getFolderOptions();
	},
	methods: {
		// Reject oversized files client-side before upload; the backend also enforces this limit.
		async onFilesChange(files) {
			const oversized = files.filter((item) => item instanceof File && item.size > MAX_FILESIZE_BYTES);

			if (oversized.length === 0) {
				return;
			}

			this.files = files.filter((item) => !(item instanceof File) || item.size <= MAX_FILESIZE_BYTES);

			const details = oversized.map((file) => file.name).join('\n');
			await this.alertError(
				this.translate('COM_EMUNDUS_RESOURCES_IMPORT_ERROR_FILESIZE').replace('%d', MAX_FILESIZE_MB),
				details,
			);
		},
		async onImport() {
			const files = this.files.filter((item) => item instanceof File);
			if (files.length === 0 || this.isRunning) {
				return;
			}

			this.isRunning = true;

			try {
				const targetFolderId = this.targetFolderId ? Number(this.targetFolderId) : null;
				const result = await resourceService.uploadFiles(files, targetFolderId);

				if (result.imported.length > 0) {
					this.$emit('update-items');
				}

				if (result.status) {
					this.$emit('close');

					await this.alertSuccess(this.translate('COM_EMUNDUS_RESOURCES_IMPORT_SUCCESS'));
				} else {
					// Show the explicit per-file reason (bad format, dangerous content, size…) returned
					// by the backend; fall back to the file name when no message is available.
					const details = result.errors.map((error) => error.msg || error.name).join('\n');
					await this.alertError(this.translate('COM_EMUNDUS_RESOURCES_IMPORT_ERROR'), details);
				}
			} catch (e) {
				await this.alertError(this.translate('COM_EMUNDUS_RESOURCES_IMPORT_ERROR'), e.message);
			} finally {
				this.isRunning = false;
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-6 tw-p-2">
		<ModalHeader :title="translate('COM_EMUNDUS_RESOURCES_IMPORT_DOCUMENT')" @close="$emit('close')" />

		<Fileupload
			v-model="files"
			:label="translate('COM_EMUNDUS_RESOURCES_IMPORT_DOCUMENT_LABEL')"
			:help-text="translate('COM_EMUNDUS_RESOURCES_IMPORT_DOCUMENT_HELPTEXT')"
			:multiple="true"
			:disabled="isRunning"
			:dropzone-text="translate('COM_EMUNDUS_RESOURCES_IMPORT_DROPZONE')"
			@change="onFilesChange"
		/>

		<div class="tw-flex tw-flex-col tw-gap-1">
			<label for="import-target-folder" class="tw-text-sm tw-font-medium">
				{{ translate('COM_EMUNDUS_RESOURCES_IMPORT_TARGET_FOLDER') }}
			</label>
			<select id="import-target-folder" v-model="targetFolderId" :disabled="isRunning">
				<option v-for="option in folderOptions" :key="option.value" :value="option.value">
					{{ option.label }}
				</option>
			</select>
		</div>

		<div class="tw-flex tw-justify-center">
			<Button
				:label="translate('COM_EMUNDUS_RESOURCES_IMPORT_CONFIRM')"
				variant="primary"
				:disabled="!hasFiles || isRunning"
				:loading="isRunning"
				@click="onImport"
			/>
		</div>
	</div>
</template>

<style scoped></style>
