<template>
	<section>
		<div class="em-h4 tw-mb-4">
			{{ translate('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS') }}
		</div>
		<div id="no-aspects" class="tw-flex tw-items-center" v-if="aspects.length < 1 && upload">
			<input type="file" id="aspect-file" class="em-m-0" accept=".xml" />
			<div class="em-w-33 em-ml-16 tw-btn-primary tw-cursor-pointer" @click="uploadAspectFile">
				{{ translate('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_UPLOAD') }}
			</div>
		</div>
		<div id="aspects" v-else>
			<div class="em-h5 tw-mb-4">
				{{ translate('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_MAPPING') }}
			</div>
			<div v-for="aspect in aspects" :key="aspect.name" class="tw-mb-4">
				<div class="tw-flex tw-items-center tw-justify-between">
					<input type="text" v-model="aspect.label" disabled />
					<span class="material-symbols-outlined">sync_alt</span>
					<select v-model="aspect.mapping" @change="updateAspectMapping">
						<option v-for="tag in tags" :key="tag.id" :value="tag.id">
							{{ tag.tag }}
						</option>
					</select>
				</div>
			</div>

			<div v-if="upload">
				<div class="em-h6 tw-mb-4">
					{{ translate('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_UPLOAD_ADD_FROM_FILE') }}
				</div>
				<div id="add-aspects-from-file" class="tw-flex tw-items-center">
					<input type="file" id="update-aspect-file" accept=".xml" />
					<div class="tw-btn-primary tw-cursor-pointer" @click="updateAspectListFromFile">
						{{ translate('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_UPLOAD_ADD') }}
					</div>
				</div>
			</div>
		</div>
	</section>
</template>

<script>
import syncService from '../../../../services/sync';

export default {
	name: 'Aspects',
	props: {
		aspects: {
			type: Array,
			default: [],
		},
		upload: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			tags: [],
		};
	},
	mounted() {
		this.getTags();
	},
	methods: {
		getTags() {
			syncService.getSetupTags().then((response) => {
				this.tags = response.data;
			});
		},
		uploadAspectFile() {
			let file = document.getElementById('aspect-file').files[0];

			if (typeof file !== 'undefined' && file !== null) {
				syncService.uploadAspectFile(file).then((response) => {
					this.aspects = response.data.data;
					this.$emit('update-aspects', this.aspects);
				});
			} else {
				Swal.fire({
					type: 'warning',
					title: this.translate('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_MISSING_ASPECT_FILE'),
					timer: 5000,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'em-swal-single-action',
					},
				});
			}
		},
		updateAspectListFromFile() {
			let file = document.getElementById('update-aspect-file').files[0];
			syncService.updateAspectListFromFile(file).then((response) => {
				this.aspects = response.data.data;
				this.$emit('update-aspects', this.aspects);
			});
		},
		updateAspectMapping(event) {
			this.$emit('update-aspects', this.aspects);
		},
	},
};
</script>

<style lang="scss">
#aspect-file {
	margin: 0 !important;
	height: auto !important;
}

#add-aspects-from-file {
	.tw-btn-primary {
		width: fit-content;
	}
}
</style>
