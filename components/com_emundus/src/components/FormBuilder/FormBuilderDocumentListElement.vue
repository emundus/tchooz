<template>
	<div id="form-builder-document-list-element" @click="editDocument">
		<div class="section-card tw-mb-8 tw-mt-8 tw-flex tw-w-full tw-flex-col">
			<div class="section-identifier tw-cursor-pointer tw-bg-profile-full">
				{{ translate('COM_EMUNDUS_FORM_BUILDER_DOCUMENT') }}
				{{ documentIndex }} / {{ totalDocuments }}
			</div>
			<div class="section-content" :class="{ closed: closedSection }">
				<div v-if="documentData.id">
					<div class="tw-flex tw-w-full tw-items-center tw-justify-between">
						<span class="section-title">{{ documentData.name[shortDefaultLang] }}</span>

						<div>
							<span
								class="material-symbols-outlined hover-opacity tw-cursor-pointer"
								@click="moveDocument('up')"
								title="Move section upwards"
								>keyboard_double_arrow_up</span
							>
							<span
								class="material-symbols-outlined hover-opacity tw-cursor-pointer"
								@click="moveDocument('down')"
								title="Move section downwards"
								>keyboard_double_arrow_down</span
							>
							<span
								class="material-symbols-outlined hover-opacity tw-cursor-pointer tw-text-red-600"
								@click="deleteDocument"
								>delete</span
							>
						</div>
					</div>
					<p>{{ documentData.description[shortDefaultLang] }}</p>
					<p>
						{{ translate('COM_EMUNDUS_FORM_BUILDER_ALLOWED_TYPES') }} :
						{{ documentData.allowed_types }}
					</p>
					<p>
						{{ translate('COM_EMUNDUS_FORM_BUILDER_MAX_DOCUMENTS') }} :
						{{ documentData.nbmax }}
					</p>
				</div>
				<div v-else>
					<span class="section-title">{{ document.label }}</span>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import formService from '@/services/form';
import formBuilderMixin from '@/mixins/formbuilder.js';
import { useFormBuilderStore } from '@/stores/formbuilder.js';

export default {
	name: 'FormBuilderDocumentListElement',
	props: {
		document: {
			type: Object,
			required: true,
		},
		totalDocuments: {
			type: Number,
			default: 1,
		},
		documentIndex: {
			type: Number,
			default: 1,
		},
		profile_id: {
			type: Number,
			required: true,
		},
	},
	mixins: [formBuilderMixin],
	data() {
		return {
			closedSection: false,
			documentData: {},
			canBeRemoved: false,
			reasonCantRemove: '',
		};
	},
	setup() {
		return {
			formBuilderStore: useFormBuilderStore(),
		};
	},
	created() {
		if (this.document.docid) {
			this.getDocumentModel(this.document.docid);
			this.checkIfDocumentCanBeDeleted();
		}
	},
	methods: {
		moveDocument(direction) {
			this.$emit('move-document', this.document, direction);
		},
		getDocumentModel(documentId = null, from_store = true) {
			this.models = from_store ? this.formBuilderStore.getDocumentModels : [];
			this.documentData = {};

			if (this.models.length > 0) {
				const foundModel = this.models.find((model) => model.id === documentId);

				if (foundModel) {
					this.documentData = foundModel;
				} else {
					formService.getDocumentModels(documentId).then((response) => {
						if (response.status) {
							this.documentData = response.data;
						}
					});
				}
			} else {
				formService.getDocumentModels(documentId).then((response) => {
					if (response.status) {
						this.documentData = response.data;
					}
				});
			}
		},
		editDocument(event) {
			if (event.target.id === 'delete-section') {
				return;
			}

			this.$emit('edit-document');
		},
		deleteDocument() {
			this.swalConfirm(
				this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_DOCUMENT'),
				this.document.label,
				this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_DOCUMENT_CONFIRM'),
				this.translate('JNO'),
				() => {
					formService.removeDocumentFromProfile(this.document.id).then((response) => {
						this.$emit('delete-document', this.document.id);
					});
				},
			);
		},
		checkIfDocumentCanBeDeleted() {
			formService.checkIfDocumentCanBeDeletedForProfile(this.document.docid, this.profile_id).then((response) => {
				if (response.status) {
					if (response.data.can_be_deleted) {
						this.canBeRemoved = true;
						this.reasonCantRemove = '';
					} else {
						this.canBeRemoved = false;
						this.reasonCantRemove = response.data.reason;
					}
				} else {
					this.canBeRemoved = false;
				}
			});
		},
	},
	watch: {
		document: {
			handler(newValue) {
				this.getDocumentModel(newValue.docid, false);
			},
			deep: true,
		},
	},
};
</script>

<style lang="scss">
#form-builder-document-list-element {
	.section-card {
		.section-identifier {
			color: white;
			padding: 8px 24px;
			border-radius: 4px 4px 0 0;
			display: flex;
			align-self: flex-end;
		}

		.section-content {
			padding: 32px;
			border-top: 4px solid var(--em-profile-color);
			background-color: var(--neutral-0);
			width: 100%;
			transition: all 0.3s ease-in-out;

			.hover-opacity {
				opacity: 0;
				pointer-events: none;
				transition: all 0.3s;
			}

			&:hover {
				.hover-opacity {
					opacity: 1;
					pointer-events: all;
				}
			}

			&.closed {
				max-height: 93px;
				overflow: hidden;
			}

			.section-title {
				font-weight: 800;
				font-size: 20px;
				line-height: 25px;
			}

			.empty-section-element {
				border: 1px dashed;
				opacity: 0.2;
				padding: 11px;
				margin: 32px 0 0 0;
			}
		}
	}
}
</style>
