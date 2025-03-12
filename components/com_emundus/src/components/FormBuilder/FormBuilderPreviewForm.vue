<template>
	<div id="form-builder-preview-form" class="tw-h-full tw-w-full" :class="{ loading: loading }">
		<div v-if="!loading">
			<p class="tw-mb-4 tw-w-full tw-text-end tw-text-xs">{{ form_label }}</p>
			<div class="preview-groups tw-flex tw-flex-col">
				<section v-for="(group, index) in formData.groups" :key="group.id" class="form-builder-page-section tw-mb-2">
					<div class="section-card tw-flex tw-flex-col">
						<div class="section-identifier tw-flex tw-items-center tw-bg-profile-full">
							<span class="text-xxs"
								>{{ translate('COM_EMUNDUS_FORM_BUILDER_SECTION') }} {{ index + 1 }} /
								{{ formData.groups.length }}</span
							>
						</div>
						<div class="section-content tw-w-full">
							<p class="tw-w-full tw-text-end tw-text-xxs">
								{{ group.label.replace('Model - ', '') }}
							</p>
						</div>
					</div>
				</section>
			</div>
		</div>
		<skeleton v-else height="100%" width="100%"></skeleton>
	</div>
</template>

<script>
import formService from '@/services/form.js';
import Skeleton from '@/components/Skeleton.vue';

export default {
	name: 'FormBuilderPreviewForm',
	components: { Skeleton },
	props: {
		form_id: {
			type: Number,
			required: true,
		},
		form_label: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			loading: true,
			formData: {},
		};
	},
	created() {
		formService.getPageGroups(this.form_id).then((response) => {
			if (response.status) {
				response.data.groups = response.data.groups.filter((group) => {
					return Number(group.published) === 1;
				});
				this.formData = response.data;
			}

			this.loading = false;
		});
	},
	methods: {},
};
</script>

<style lang="scss">
#form-builder-preview-form {
	padding: 8px !important;
	font-size: 6px;
	background-color: #f1f1f1 !important;
	overflow: hidden;

	&.loading {
		padding: 0 !important;
		border: unset !important;
	}

	p.em-font-size-8 {
		font-size: 8px !important;
	}

	.section-identifier {
		padding: 2px;
		border-radius: 2px 2px 0 0;
	}

	.section-content {
		border-top-width: 2px;
		padding: 2px 4px;
		min-height: 40px;
	}

	.preview-groups {
		justify-content: flex-start;

		section {
			width: 90%;
		}
	}
}
</style>
