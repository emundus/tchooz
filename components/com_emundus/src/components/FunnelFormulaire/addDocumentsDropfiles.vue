<template>
	<div id="documents-dropfiles">
		<div class="w-form">
			<vue-dropzone
				:key="dropzoneOptions.maxFilesize"
				ref="dropzone"
				id="customdropzone"
				style="width: 100%"
				:include-styling="false"
				:options="dropzoneOptions"
				:useCustomSlot="true"
				v-on:vdropzone-file-added="afterAdded"
				v-on:vdropzone-removed-file="afterRemoved"
				v-on:vdropzone-success="onComplete"
				v-on:vdropzone-error="catchError"
			>
				<div class="dropzone-custom-content" id="dropzone-message">
					{{ DropHere }}
				</div>
			</vue-dropzone>

			<hr />

			<draggable
				v-model="documents"
				id="campaignDocs"
				style="margin: 0"
				handle=".handle"
				class="tw-flex tw-items-center"
				chosen-class="em-grabbing"
				v-bind="dragOptions"
				@end="updateDocumentsOrder"
			>
				<transition-group
					type="transition"
					:value="!drag ? 'flip-list' : null"
					class="handle tw-grid tw-w-full tw-grid-cols-3 tw-gap-6"
				>
					<div
						:id="'itemDoc' + document.id"
						v-for="(document, indexDoc) in documents"
						:key="document.id"
						class="em-document-dropzone-card handle tw-mr-2 tw-cursor-grab"
					>
						<button type="button" class="tw-float-right tw-bg-transparent" @click="deleteDoc(indexDoc, document.id)">
							<span class="material-symbols-outlined">close</span>
						</button>
						<div class="tw-flex tw-w-full tw-items-center tw-justify-center">
							<div class="em-edit-cursor tw-flex tw-flex-col tw-items-center" @click="editName(document)">
								<img
									v-if="document.ext === 'pdf'"
									src="@/assets/images/filetype/pdf.png"
									class="em-filetype-icon"
									alt="filetype"
								/>
								<img
									v-else-if="['docx', 'doc', 'odf'].includes(document.ext)"
									src="@/assets/images/filetype/doc.png"
									class="em-filetype-icon"
									alt="filetype"
								/>
								<img
									v-else-if="['xls', 'xlsx', 'csv'].includes(document.ext)"
									src="@/assets/images/filetype/excel.png"
									class="em-filetype-icon"
									alt="filetype"
								/>
								<img
									v-else-if="['png', 'gif', 'jpg', 'jpeg'].includes(document.ext)"
									src="@/assets/images/filetype/image.png"
									class="em-filetype-icon"
									alt="filetype"
								/>
								<img
									v-else-if="['zip', 'rar'].includes(document.ext)"
									src="@/assets/images/filetype/zip.png"
									class="em-filetype-icon"
									alt="filetype"
								/>
								<img
									v-else-if="['svg'].includes(document.ext)"
									src="@/assets/images/filetype/svg.png"
									class="em-filetype-icon"
									alt="filetype"
								/>
								<div class="tw-mt-2">
									<span class="em-overflow-ellipsis em-max-width-250 tw-mr-1">{{ document.title }}</span>
								</div>
							</div>
						</div>
						<hr />
						<div id="itemDocSize">
							<span
								><strong>{{ translate('COM_EMUNDUS_ONBOARD_FILE_SIZE') }} : </strong></span
							>
							<span>{{ formatBytes(document.size) }}</span>
						</div>
					</div>
				</transition-group>
			</draggable>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import Swal from 'sweetalert2';
import vueDropzone from 'vue2-dropzone-vue3';
import { VueDraggableNext } from 'vue-draggable-next';
import campaignService from '@/services/campaign.js';

const getTemplate = () => `
<div class="dz-preview dz-file-preview">
  <div class="dz-image">
    <div data-dz-thumbnail-bg></div>
  </div>
  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
  <div class="dz-error-message"><span data-dz-errormessage></span></div>
  <div class="dz-success-mark"><i class="fa fa-check"></i></div>
  <div class="dz-error-mark"><i class="fa fa-close"></i></div>
</div>
`;

export default {
	name: 'addDocumentsDropfiles',

	components: {
		vueDropzone,
		draggable: VueDraggableNext,
	},

	props: {
		funnelCategorie: String,
		profileId: Number,
		campaignId: Number,
		langue: String,
		manyLanguages: Number,
	},

	data() {
		return {
			dropzoneOptions: {
				url:
					window.location.origin +
					'/index.php?option=com_emundus&controller=settings&task=uploaddropfiledoc&cid=' +
					this.campaignId,
				maxFilesize: 10,
				maxFiles: 1,
				autoProcessQueue: true,
				addRemoveLinks: true,
				thumbnailWidth: null,
				thumbnailHeight: null,
				acceptedFiles: 'image/*,application/pdf,.doc,.csv,.xls,.xlsx,.docx,.odf,.zip',
				previewTemplate: getTemplate(),
				dictCancelUpload: this.translate('COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD'),
				dictCancelUploadConfirmation: this.translate('COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD_CONFIRMATION'),
				dictRemoveFile: this.translate('COM_EMUNDUS_ONBOARD_REMOVE_FILE'),
				dictInvalidFileType: this.translate('COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE'),
				dictFileTooBig: this.translate('COM_EMUNDUS_ONBOARD_FILE_TOO_BIG'),
				dictMaxFilesExceeded: this.translate('COM_EMUNDUS_ONBOARD_MAX_FILES_EXCEEDED'),
				uploadMultiple: false,
			},
			documents: [],
			Retour: this.translate('COM_EMUNDUS_ONBOARD_ADD_RETOUR'),
			Continuer: this.translate('COM_EMUNDUS_ONBOARD_ADD_CONTINUER'),
			DropHere: this.translate('COM_EMUNDUS_ONBOARD_DROP_FILE_HERE'),
			Error: this.translate('COM_EMUNDUS_ONBOARD_ERROR'),
			DocumentName: this.translate('COM_EMUNDUS_ONBOARD_DOCUMENT_NAME'),
			drag: false,
			loading: false,
		};
	},

	methods: {
		getMediaSize() {
			campaignService
				.get('getmediasize')
				.then((response) => {
					if (response.status) {
						this.dropzoneOptions.maxFilesize = parseInt(response.size);
					}
				})
				.finally(() => {
					this.loading = false;
				});
		},
		getDocumentsDropfiles() {
			this.loading = true;

			campaignService
				.get('getdocumentsdropfiles', { cid: this.campaignId })
				.then((response) => {
					this.documents = response.documents;
				})
				.finally(() => {
					this.loading = false;
				});
		},
		updateDocumentsOrder() {
			this.documents.forEach((document, index) => {
				document.ordering = index;
			});

			campaignService.reorderDropfileDocuments(this.documents);
		},
		editName(doc) {
			Swal.fire({
				title: '',
				html:
					'<div class="form-group campaign-label">' +
					'<label for="campLabel">' +
					this.DocumentName +
					'</label><input type="text" class="tw-mt-2" maxlength="200" id="label_' +
					doc.id +
					'" value="' +
					doc.title +
					'"/>' +
					'</div>',
				showCloseButton: true,
				allowOutsideClick: false,
				confirmButtonText: this.translate('COM_EMUNDUS_OK'),
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					actions: 'em-swal-single-action',
					content: 'text-start',
				},
			}).then((value) => {
				if (value) {
					let newname = document.getElementById('label_' + doc.id).value;
					if (newname.length > 200) {
						newname = newname.substring(0, 200);
					}

					campaignService.editDropfileDocument(doc.id, newname).then(() => {
						doc.title = newname;
					});
				}
			});
		},
		deleteDoc(index, id) {
			this.documents.splice(index, 1);

			campaignService.deleteDropfileDocument(id);
		},
		formatBytes(bytes, decimals = 2) {
			if (bytes === 0) return '0 Bytes';

			const k = 1024;
			const dm = decimals < 0 ? 0 : decimals;
			const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

			const i = Math.floor(Math.log(bytes) / Math.log(k));

			return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
		},

		afterAdded() {
			document.getElementById('dropzone-message').style.display = 'none';
		},
		afterRemoved() {
			if (this.$refs.dropzone.getAcceptedFiles().length === 0) {
				document.getElementById('dropzone-message').style.display = 'block';
			}
		},
		onComplete: function (response) {
			this.documents.push(JSON.parse(response.xhr.response));
			this.$refs.dropzone.removeFile(response);
		},
		catchError: function (file, message) {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_ONBOARD_ERROR'),
				text: message,
				type: 'error',
				showCancelButton: false,
				showConfirmButton: false,
				timer: 3000,
			});
			this.$refs.dropzone.removeFile(file);
		},
	},

	computed: {
		dragOptions() {
			return {
				animation: 200,
				group: 'description',
				disabled: false,
				ghostClass: 'ghost',
			};
		},
	},

	created() {
		this.getDocumentsDropfiles();
		this.getMediaSize();
	},
};
</script>
<style scoped lang="scss">
.fa-file-upload {
	font-size: 25px;
	margin-right: 20px;
}

.list-group-item {
	border: 2px solid #ececec;
	margin-bottom: 10px;
	border-radius: 5px;
}

/**** CUSTOM VUE DROPZONE ****/
#customdropzone {
	letter-spacing: 0.2px;
	background: #fff;
	color: #c5c8ce;
	transition: background-color 0.2s linear;
	height: 200px;
	padding: 40px;
	border: dashed;
	border-radius: 5px;
	justify-content: center;
	align-items: center;
	display: flex;
	cursor: pointer;

	.dz-preview {
		width: 100%;
		display: inline-block;
		text-align: center;

		.dz-image {
			width: auto;
			height: 100px;

			> div {
				width: inherit;
				height: inherit;
				background-size: contain;
				background-repeat: no-repeat;
				background-position: center;
			}

			> img {
				width: 100%;
			}
		}

		.dz-details {
			color: black;
			transition: opacity 0.2s linear;
			text-align: center;
		}
	}

	.dz-success-mark {
		display: none;
	}
}

.dz-default.dz-message {
	text-align: center !important;
}

.dz-error-mark {
	display: none;
}

/**** END ****/

.em-document-dropzone-card {
	background: var(--neutral-100);
	border-radius: 5px;
	padding: 16px 24px;
}

.em-filetype-icon {
	width: 50px;
}
</style>
