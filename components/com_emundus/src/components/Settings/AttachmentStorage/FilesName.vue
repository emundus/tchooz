<template>
	<div class="tw-mt-4">
		<div class="tw-flex tw-items-center tw-justify-between">
			<div class="em-h4 tw-mb-4">
				{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_WRITING') }}
			</div>
			<div class="tw-flex tw-items-center">
				<a class="em-blue-500-color em-hover-blue-500 tw-mr-4 tw-cursor-pointer" href="/export-tags" target="_blank">{{
					translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_TAGS_LIST')
				}}</a>
				<div class="em-blue-500-color tw-cursor-pointer" @click="resetName">
					{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_RESET') }}
				</div>
			</div>
		</div>

		<div
			class="em-name-preview tw-mb-4 tw-flex tw-items-center"
			:class="selectedTags.length === 0 ? 'em-name-preview-error' : ''"
		>
			<div v-for="(tag, index) in selectedTags" class="tw-flex tw-items-center">
				<span>{{ tag.value }}</span>
				<div v-if="index + 1 !== selectedTags.length">
					{{ selectedSeparator }}
				</div>
			</div>
		</div>
		<div class="tw-mb-4 tw-text-red-600" v-if="selectedTags.length === 0">
			{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_SELECT_A_TAG') }}
		</div>

		<div class="tw-flex tw-items-center">
			<multiselect
				v-model="new_tag"
				label="label"
				track-by="value"
				:options="tags"
				:multiple="true"
				:taggable="true"
				:tag-placeholder="'Ajouter une balise'"
				@tag="addTag"
				select-label=""
				selected-label=""
				deselect-label=""
				:placeholder="'Ajouter une balise'"
			></multiselect>

			<div v-for="(tag, index) in selectedTags" :key="index" class="em-ml-16 em-tag-preview tw-flex tw-items-center">
				<span>{{ tag.label }}</span>
				<span class="material-symbols-outlined tw-ml-2 tw-cursor-pointer" @click="removeTag(tag.value)">close</span>
			</div>
		</div>

		<div class="tw-mt-4 tw-flex tw-items-center">
			<span
				>{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_SEPARATOR') }}
				:
			</span>
			<div
				class="em-ml-16 em-separator tw-cursor-pointer"
				:class="selectedSeparator === separator ? 'em-selected-separator' : ''"
				v-for="separator in separators"
				@click="selectedSeparator = separator"
			>
				<span>{{ separator }}</span>
			</div>
		</div>
	</div>
</template>

<script>
import Multiselect from 'vue-multiselect';
import syncService from '../../../services/sync';

export default {
	name: 'FilesName',
	components: {
		Multiselect,
	},
	props: {
		name: String,
	},
	data() {
		return {
			loading: false,

			new_tag: '',
			tags: [
				{
					label: 'N° de dossier',
					value: '[FNUM]',
				},
				{
					label: 'Nom du candidat',
					value: '[APPLICANT_NAME]',
				},
				{
					label: 'Type de document',
					value: '[DOCUMENT_TYPE]',
				},
			],
			separators: ['-', '_'],
			selectedTags: [],
			selectedSeparator: '',
		};
	},
	created() {
		syncService.getSetupTags().then((response) => {
			if (response.status) {
				response.data.forEach((tag) => {
					if (
						!this.tags.find((current_tag) => {
							return current_tag.value == '[' + tag.tag + ']';
						})
					) {
						this.tags.push({
							label: tag.description,
							value: '[' + tag.tag + ']',
						});
					}
				});
			}

			if (this.name !== '') {
				this.selectedSeparator = this.name.indexOf('-') === -1 ? '_' : '-';

				let tags_regex = [];
				if (this.selectedSeparator == '_') {
					const splitted_name = this.name.split(']_[');

					splitted_name.forEach((tag, index) => {
						tag = tag.replace(/\[/g, '');
						tag = tag.replace(/]/g, '');

						if (tag !== '') {
							tags_regex.push(tag);
						}
					});
				} else {
					this.name = this.name.replace(/\[/g, '');
					this.name = this.name.replace(/]/g, '');
					tags_regex = this.name.split(this.selectedSeparator);
				}

				tags_regex.forEach((tag, index) => {
					tags_regex[index] = '[' + tag + ']';

					let tag_found = this.tags.findIndex(function (element, key) {
						if (element.value === '[' + tag + ']') return true;
					});

					if (tag_found !== -1) {
						this.selectedTags.push(this.tags[tag_found]);
					}
				});
			} else {
				this.selectedSeparator = '-';

				if (this.selectedTags.length === 0) {
					this.resetName();
				}
			}
		});
	},
	methods: {
		addTag(newTag) {
			const tag = {
				label: newTag,
				value: newTag,
			};
			this.tags.push(tag);
			this.selectedTags.push(tag);

			this.updateName();
		},

		removeTag(tag) {
			let tag_found = this.selectedTags.findIndex(function (element, index) {
				if (element.value === tag) return true;
			});

			this.selectedTags.splice(tag_found, 1);

			this.updateName();
		},

		resetName() {
			this.selectedTags = [
				{
					label: 'N° de dossier',
					value: '[FNUM]',
				},
				{
					label: 'Type de document',
					value: '[DOCUMENT_TYPE]',
				},
			];

			this.updateName();
		},

		updateName() {
			let name = '';
			this.selectedTags.forEach((tag, index) => {
				if (index != this.selectedTags.length - 1) {
					name = name + tag.value + this.selectedSeparator;
				} else {
					name += tag.value;
					this.$emit('updateName', name);
				}
			});
		},
	},

	watch: {
		new_tag: function (value) {
			if (value !== '') {
				const tag = value[0];
				this.selectedTags.push(tag);

				this.updateName();
			}

			this.new_tag = '';
		},

		selectedSeparator: function () {
			this.updateName();
		},
	},
};
</script>

<style scoped>
.em-name-preview {
	background: rgba(121, 182, 251, 0.25);
	padding: 16px 8px;
	border-radius: 4px;
}

.em-name-preview-error {
	background: rgba(255, 121, 121, 0.25);
}

.multiselect {
	width: 20%;
}

.em-tag-preview {
	padding: 16px;
	background: rgba(121, 182, 251, 0.25);
	border-radius: 4px;
	height: 50px;
	display: flex;
	align-items: center;
}

.em-separator {
	border-radius: 4px;
	padding: 4px 8px;
	background: #cecece;
	height: auto;
}

.em-selected-separator {
	background: rgba(121, 182, 251, 0.25);
}
</style>
