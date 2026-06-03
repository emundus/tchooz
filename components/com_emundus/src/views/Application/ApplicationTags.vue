<script>
import filesService from '@/services/file.js';
import Parameter from '@/components/Utils/Parameter.vue';
import ApplicationTagEdit from '@/views/Application/ApplicationTagEdit.vue';

export default {
	name: 'ApplicationTags',
	components: { Parameter },
	emits: ['update:applicationTags'],
	props: {
		application: {
			type: Object,
			default: () => ({}),
		},
		applicationTags: {
			type: Array,
			default: () => [],
		},
		tagOptions: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			tagsByApplication: [],
			availableTags: [],
			tagField: {
				param: 'application_tag',
				type: 'select',
				value: 0,
				label: 'COM_EMUNDUS_APPLICATION_TAGS_PICK',
				hideLabel: true,
				optional: true,
				displayed: true,
				options: [],
				reload: 0,
				addNew: {
					label: 'COM_EMUNDUS_APPLICATION_ADD_NEW_TAG',
					component: ApplicationTagEdit,
				},
			},
			selectedTagId: 0,
			attaching: false,
		};
	},
	computed: {
		attachedIds() {
			return this.tagsByApplication.map((association) => association.label.id);
		},
		parameterComponentsProps() {
			return {
				fnum: this.application?.fnum || '',
			};
		},
	},
	created() {
		this.tagsByApplication = [...this.applicationTags];
		this.availableTags = [...this.tagOptions];
		this.refreshSelectOptions();
	},
	methods: {
		refreshSelectOptions() {
			const placeholder = {
				value: 0,
				label: this.translate('COM_EMUNDUS_APPLICATION_TAGS_PICK_PLACEHOLDER'),
			};

			const options = this.availableTags
				.filter((tag) => !this.attachedIds.includes(tag.id))
				.map((tag) => ({
					value: tag.id,
					label: tag.label,
				}));

			this.tagField.options = [placeholder, ...options];
			this.tagField.value = 0;
			this.selectedTagId = 0;
			this.tagField.reload++;
		},
		onTagValueUpdated(_parameter, _oldValue, newValue) {
			this.selectedTagId = parseInt(newValue) || 0;
		},
		async refreshApplicationTags() {
			if (!this.application?.fnum) {
				return;
			}

			const response = await filesService.getApplicationTags(this.application.fnum);
			if (response && response.status) {
				this.tagsByApplication = response.data || [];
				this.$emit('update:applicationTags', this.tagsByApplication);
				this.refreshSelectOptions();
			}
		},
		async refreshAvailableTags() {
			const response = await filesService.getAvailableTags();
			if (response && response.status) {
				this.availableTags = response.data || [];
				this.refreshSelectOptions();
			}
		},
		async attachSelectedTag() {
			if (!this.selectedTagId || this.attaching) {
				return;
			}

			const response = await this.addTagToApplication(this.selectedTagId);

			if (response && response.status) {
				await this.refreshApplicationTags();
			}
		},
		async addTagToApplication(tagId) {
			this.attaching = true;
			const response = await filesService.addApplicationTag(this.application.fnum, tagId);
			this.attaching = false;

			return response;
		},
		async onNewTagCreated(parameter, newTagId) {
			if (newTagId > 0) {
				await this.addTagToApplication(newTagId);
				await this.refreshAvailableTags();
				await this.refreshApplicationTags();
			}
		},
		async removeApplicationTag(associationId) {
			const association = this.tagsByApplication.find((tag) => tag.id === associationId);
			if (!association) {
				return;
			}

			const response = await filesService.deleteApplicationTag(association.label.id, this.application.fnum);
			if (response && response.status) {
				await this.refreshApplicationTags();
			}
		},
	},
};
</script>

<template>
	<div id="application-tags" class="tw-w-full">
		<div id="application-tags-list" class="tw-mb-4 tw-flex tw-flex-wrap tw-gap-2">
			<div
				v-for="tag in tagsByApplication"
				:key="tag.id"
				:title="tag.label.label"
				class="tw-w-full tw-rounded-coordinator tw-border tw-bg-white tw-p-4"
			>
				<div class="tw-flex tw-items-center tw-justify-between">
					<div>
						<div class="tag-header-information tw-mb-2">
							<p class="tw-text-xs tw-text-neutral-500">{{ tag.user }} - {{ tag.created }}</p>
						</div>
						<div class="sticker tw-flex tw-items-center tw-gap-2" :class="tag.label.class">
							<span class="circle tw-bg-white"></span>
							<span class="tw-text-nowrap tw-font-semibold tw-text-white">{{ tag.label.label }}</span>
						</div>
					</div>
					<div class="actions">
						<span
							class="material-symbols-outlined tw-cursor-pointer tw-text-red-500"
							@click="removeApplicationTag(tag.id)"
						>
							delete
						</span>
					</div>
				</div>
			</div>

			<p v-if="tagsByApplication.length === 0" class="tw-w-full tw-text-sm tw-italic tw-text-neutral-500">
				{{ translate('COM_EMUNDUS_APPLICATION_TAGS_EMPTY') }}
			</p>
		</div>

		<hr class="tw-w-full" />

		<div id="form" class="tw-flex tw-w-full tw-items-end tw-gap-2">
			<div class="tw-flex-1">
				<Parameter
					:key="tagField.reload"
					:parameter-object="tagField"
					:components-props="parameterComponentsProps"
					help-text-type="above"
					@valueUpdated="onTagValueUpdated"
					@newValueAdded="onNewTagCreated"
				/>
			</div>

			<button
				type="button"
				class="tw-btn-primary tw-cursor-pointer"
				:disabled="!selectedTagId || attaching"
				@click="attachSelectedTag"
			>
				{{ translate('COM_EMUNDUS_APPLICATION_TAGS_ATTACH') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
