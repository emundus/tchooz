<template>
	<div id="attachment-edit">
		<div class="wrapper">
			<h2 class="title">{{ attachment.value }}</h2>
			<div class="editable-data tw-flex tw-flex-col tw-gap-4">
				<div>
					<label for="description">Description</label>
					<textarea
						v-if="attachment.allowed_types !== 'video'"
						name="description"
						id="description"
						type="text"
						v-model="attachmentDescription"
						:disabled="!canUpdate"
						@focusout="saveChanges"
					>
					</textarea>
					<span v-else v-html="attachmentDescription"></span>
				</div>

				<div
					class="valid-state tw-flex tw-flex-col"
					:class="{
						success: attachmentIsValidated == 1,
						warning: attachmentIsValidated == 2,
						error: attachmentIsValidated == 0,
					}"
				>
					<label for="status">{{ translate('COM_EMUNDUS_ATTACHMENTS_CHECK') }}</label>
					<select
						name="status"
						v-model="attachmentIsValidated"
						@change="updateAttachmentStatus"
						:disabled="!canUpdate || is_applicant == 1"
					>
						<option value="1">{{ translate('VALID') }}</option>
						<option value="0">{{ translate('INVALID') }}</option>
						<option value="2">
							{{ translate('COM_EMUNDUS_ATTACHMENTS_WARNING') }}
						</option>
						<option value="-2">
							{{ translate('COM_EMUNDUS_ATTACHMENTS_WAITING') }}
						</option>
					</select>
				</div>
				<div v-if="canUpdate || (is_applicant == 1 && attachmentIsValidated == 0)">
					<label for="replace">{{ translate('COM_EMUNDUS_ATTACHMENTS_REPLACE') }}</label>
					<input type="file" name="replace" @change="updateFile" :accept="allowedType" />
				</div>
				<div class="tw-flex tw-items-center" v-if="is_applicant != 1">
					<div class="em-toggle">
						<input
							type="checkbox"
							class="em-toggle-check tw-mt-2"
							id="can_be_viewed"
							name="can_be_viewed"
							v-model="attachmentCanBeViewed"
							:disabled="!canUpdate"
							@change="saveChanges"
						/>
						<strong class="b em-toggle-switch"></strong>
						<strong class="b em-toggle-track"></strong>
					</div>
					<span for="can_be_viewed" class="tw-ml-2 tw-flex tw-items-center"
						>{{ translate('COM_EMUNDUS_ATTACHMENTS_CAN_BE_VIEWED') }}
					</span>
				</div>
				<div class="tw-flex tw-items-center" v-if="is_applicant != 1">
					<div class="em-toggle">
						<input
							type="checkbox"
							class="em-toggle-check tw-mt-2"
							id="can_be_deleted"
							name="can_be_deleted"
							v-model="attachmentCanBeDeleted"
							:disabled="!canUpdate"
							@change="saveChanges"
						/>
						<strong class="b em-toggle-switch"></strong>
						<strong class="b em-toggle-track"></strong>
					</div>
					<span for="can_be_deleted" class="tw-ml-2 tw-flex tw-items-center"
						>{{ translate('COM_EMUNDUS_ATTACHMENTS_CAN_BE_DELETED') }}
					</span>
				</div>
			</div>

			<div class="non-editable-data">
				<div
					v-if="attachment.category && this.categories[attachment.category] && columns.includes('category')"
					class="tw-gap-[12px] tw-py-2"
				>
					<label class="tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ATTACHMENTS_CATEGORY') }}</label>
					<span class="tw-text-right">{{ this.categories[attachment.category] }}</span>
				</div>
				<div v-if="columns.includes('date')" class="tw-gap-[12px] tw-py-2">
					<label class="tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ATTACHMENTS_SEND_DATE') }}</label>
					<span class="tw-text-right">{{ formattedDate(attachment.timedate) }}</span>
				</div>
				<div v-if="attachment.user_id && canSee" class="tw-gap-[12px] tw-py-2">
					<label class="tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ATTACHMENTS_UPLOADED_BY') }}</label>
					<span class="tw-text-right">{{ attachment.user_name }}</span>
				</div>
				<div v-if="sign && attachment.signers && columns.includes('sign')" class="tw-gap-[12px] tw-py-2">
					<label class="tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ATTACHMENTS_SIGNERS') }}</label>
					<span class="tw-text-right" v-html="attachment.signers"></span>
				</div>
				<div v-if="attachment.modified && columns.includes('modified')" class="tw-gap-[12px] tw-py-2">
					<label class="tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ATTACHMENTS_MODIFICATION_DATE') }}</label>
					<span class="tw-text-right">{{ formattedDate(attachment.modified) }}</span>
				</div>
				<div v-if="attachment.modified_by && canSee && columns.includes('modified_by')" class="tw-gap-[12px] tw-py-2">
					<label class="tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ATTACHMENTS_MODIFIED_BY') }}</label>
					<span class="tw-text-right">{{ attachment.modified_user_name }}</span>
				</div>
				<button
					class="tw-btn-primary tw-mt-4 tw-w-full"
					v-if="attachment.signed_file && attachment.original_upload_id"
					@click="$emit('change-attachment', attachment.original_upload_id)"
				>
					{{ translate('COM_EMUNDUS_ATTACHMENTS_OPEN_ORIGINAL_VALUE') }}
				</button>
				<button
					class="tw-btn-primary tw-mt-4 tw-w-full"
					v-else-if="!attachment.signed_file && attachment.signed_upload_id"
					@click="$emit('change-attachment', attachment.signed_upload_id)"
				>
					{{ translate('COM_EMUNDUS_ATTACHMENTS_OPEN_SIGNED_VALUE') }}
				</button>
				<!-- TODO: add file size -->
			</div>
		</div>

		<div class="tw-flex tw-w-full tw-items-center tw-justify-between">
			<div id="toggle-display">
				<span
					v-if="displayed"
					class="material-symbols-outlined displayed tw-cursor-pointer"
					@click="toggleDisplay(false)"
				>
					chevron_right
				</span>
				<span v-else class="material-symbols-outlined not-displayed tw-cursor-pointer" @click="toggleDisplay(true)">
					menu_open
				</span>
			</div>
		</div>
		<div v-if="error && instanciated" class="error-msg">{{ errorMessage }}</div>
	</div>
</template>

<script>
import attachmentService from '@/services/attachment';
import mixin from '../../mixins/mixin.js';
import alerts from '@/mixins/alerts.js';

import { useAttachmentStore } from '@/stores/attachment.js';
import { useGlobalStore } from '@/stores/global.js';
import { useUserStore } from '@/stores/user.js';
import { storeToRefs } from 'pinia';
import { watch } from 'vue';

export default {
	name: 'AttachmentEdit',
	props: {
		fnum: {
			type: String,
			required: true,
		},
		isDisplayed: {
			type: Boolean,
			default: true,
		},
		is_applicant: {
			type: String,
			default: null,
		},
		sign: {
			type: Boolean,
			default: true,
		},
		columns: {
			type: Array,
			default() {
				return [
					'check',
					'name',
					'date',
					'desc',
					'category',
					'status',
					'user',
					'modified_by',
					'modified',
					'permissions',
					'sync',
					'sign',
				];
			},
		},
	},
	mixins: [mixin, alerts],
	emits: ['change-attachment', 'update-displayed'],
	data() {
		return {
			instanciated: false,
			displayed: true,
			attachment: {},
			categories: {},
			file: null,
			canUpdate: false,
			canSee: true,
			error: false,
			errorMessage: '',
			attachmentIsValidated: '-2',
			attachmentCanBeViewed: false,
			attachmentCanBeDeleted: false,
			attachmentDescription: '',
		};
	},
	mounted() {
		this.displayed = this.isDisplayed;
		this.canUpdate = useUserStore().rights[this.fnum] ? useUserStore().rights[this.fnum].canUpdate : false;
		this.canSee = !useGlobalStore().anonyme;

		const attachmentStore = useAttachmentStore();
		this.categories = attachmentStore.categories;

		const { selectedAttachment } = storeToRefs(attachmentStore);
		watch(selectedAttachment, () => {
			const keys = Object.keys(selectedAttachment);

			if (keys.length !== 0) {
				this.setAttachment(selectedAttachment);
			}
		});

		this.setAttachment(attachmentStore.selectedAttachment);
	},
	methods: {
		setAttachment(attachment) {
			this.instanciated = false;
			this.attachment = attachment;
			this.attachmentCanBeViewed = this.attachment.can_be_viewed == '1';
			this.attachmentCanBeDeleted = this.attachment.can_be_deleted == '1';
			this.attachmentDescription = this.attachment.upload_description != null ? this.attachment.upload_description : '';
			if (this.attachment.is_validated != null) {
				this.attachmentIsValidated = this.attachment.is_validated;
			} else {
				this.attachmentIsValidated = '-2';
			}

			if (this.is_applicant == 1 && this.attachment.can_be_deleted == 1 && this.attachmentIsValidated == 0) {
				this.canUpdate = true;
			}
			this.instanciated = true;
		},
		async saveChanges() {
			if (!this.instanciated) {
				return;
			}

			let formData = new FormData();

			const canBeViewed = this.attachmentCanBeViewed ? '1' : '0';
			const canBeDeleted = this.attachmentCanBeDeleted ? '1' : '0';

			formData.append('fnum', this.fnum);
			formData.append('user', useUserStore().currentUser);
			formData.append('id', this.attachment.aid);
			formData.append('description', this.attachmentDescription);
			formData.append('is_validated', this.attachmentIsValidated);
			formData.append('can_be_viewed', canBeViewed);
			formData.append('can_be_deleted', canBeDeleted);

			if (this.file) {
				formData.append('file', this.file);
			}

			const response = await attachmentService.updateAttachment(formData);

			if (response.status.update) {
				this.attachment.modified_by = useUserStore().currentUser;
				this.attachment.upload_description = this.attachmentDescription != null ? this.attachmentDescription : '';
				this.attachment.is_validated = response.data.is_validated;
				this.attachment.can_be_viewed = response.data.can_be_viewed;
				this.attachment.can_be_deleted = response.data.can_be_deleted;

				useAttachmentStore().updateAttachmentOfFnum({ fnum: this.fnum, attachment: this.attachment });

				if (response.status.file_update) {
					await this.alertSuccess('COM_EMUNDUS_ATTACHMENTS_UPDATE_FILE_SUCCESS');
					// need to update file preview
					const data = await attachmentService.getPreview(
						useUserStore().displayedUser,
						this.attachment.filename,
						this.attachment.aid,
					);

					useAttachmentStore().setPreview({ preview: data, id: this.attachment.aid });

					this.$emit('change-attachment', this.attachment.aid);
				}
			} else {
				this.showError(response.msg);
			}
		},
		updateFile(event) {
			this.file = event.target.files[0];
			this.saveChanges();
		},
		updateAttachmentStatus(event) {
			this.attachmentIsValidated = event.target.value;
			this.saveChanges();
		},
		showError(error) {
			this.error = true;
			this.errorMessage = error;

			setTimeout(() => {
				this.error = false;
				this.errorMessage = '';
			}, 3000);
		},
		toggleDisplay(displayed) {
			this.displayed = displayed;
			this.$emit('update-displayed', this.displayed);
		},
	},
	computed: {
		allowedType() {
			let allowed_type = '';

			if (this.attachment.filename) {
				allowed_type = '.' + this.attachment.filename.split('.').pop();
			}

			return allowed_type;
		},
	},
};
</script>

<style lang="scss">
#attachment-edit {
	height: 100%;
	float: right;
	border-left: 1px solid var(--border-color);
	position: relative;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	align-items: flex-start;

	.error-msg {
		position: absolute;
		margin: 10px 10px;
		top: 0;
		left: 0;
		width: calc(100% - 20px);
		background-color: var(--error-bg-color);
		color: var(--error-color);
		font-size: 1.2em;
		padding: 16px;
	}

	.wrapper {
		height: 100%;
		width: -moz-available;
		width: -webkit-fill-available;
		width: stretch;

		.title {
			margin-bottom: 16px;
		}
	}

	.editable-data {
		width: 100%;

		h2 {
			text-overflow: ellipsis;
			overflow: hidden;
		}

		select {
			width: 100%;
			height: fit-content;
			padding: 16px 8px;
			border-radius: 0;
		}
	}

	.non-editable-data {
		width: 100%;
		margin-top: 16px;

		div {
			width: 100%;
			display: flex;
			flex-direction: row;
			justify-content: space-between;
			align-items: center;
			border-bottom: 1px solid var(--border-color);

			&:last-child {
				border-bottom: none;
			}
		}
	}

	.actions {
		align-self: flex-end;
		margin-right: 20px;

		button {
			transition: all 0.3s;
			padding: var(--em-coordinator-vertical) var(--em-coordinator-horizontal);
		}
	}

	.input-group {
		margin-top: 10px;
		display: flex;
		flex-direction: column;

		[type='checkbox'] {
			width: fit-content;
		}

		input {
			height: fit-content !important;
		}
	}

	.valid-state {
		select {
			padding: 4px 8px;
			border-radius: 4px;
			background-color: var(--grey-bg-color);
			color: var(--grey-color);
			border: none;
			width: 100%;
		}

		select::-ms-expand {
			display: none !important;
		}

		&.warning {
			select {
				color: var(--warning-color);
				background-color: var(--warning-bg-color);
			}
		}

		&.success {
			select {
				color: var(--success-color);
				background-color: var(--success-bg-color);
			}
		}

		&.error {
			select {
				color: var(--error-color);
				background-color: var(--error-bg-color);
			}
		}
	}

	#toggle-display {
		.not-displayed {
			position: absolute;
			bottom: 0;
			right: 15px;
			padding: 10px;
			background: white;
			border-top-left-radius: 4px;
			border: 1px solid #ececec;
		}
	}
}
</style>
