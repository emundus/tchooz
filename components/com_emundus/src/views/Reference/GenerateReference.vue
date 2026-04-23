<script>
import Loader from '@/components/Atoms/Loader.vue';
import Button from '@/components/Atoms/Button.vue';
import referenceService from '@/services/reference.js';
import Reference from '@/components/Files/Reference.vue';

import alerts from '@/mixins/alerts.js';

export default {
	name: 'GenerateReference',
	components: { Reference, Button, Loader },
	props: {},
	mixins: [alerts],
	data: () => ({
		loading: false,
		reloadForm: 0,

		references: [],
	}),
	created() {
		this.loading = true;

		this.generateReferences();
	},
	methods: {
		/* SERVICES */
		async generateReferences() {
			referenceService
				.generate()
				.then((response) => {
					this.loading = false;
					if (response.status) {
						this.references = response.data;
					} else {
						this.alertError(response.msg, response.description);
					}
				})
				.catch(() => {
					this.loading = false;
				});
		},

		async saveReferences() {
			this.loading = true;
			referenceService
				.save(this.references)
				.then((response) => {
					this.loading = false;
					if (response.status) {
						this.alertSuccess(response.msg, response.description).then(() => {
							window.postMessage('reloadData');
						});
					} else {
						this.alertError(response.msg, response.description);
					}
				})
				.catch(() => {
					this.loading = false;
				});
		},

		oldReference(reference) {
			if (!reference.old_reference || reference.old_reference === '') {
				return '-';
			}

			return reference.old_reference;
		},
	},
};
</script>

<template>
	<div>
		<div :key="reloadForm" v-show="!loading">
			<p>
				{{ translate('COM_EMUNDUS_CUSTOM_REFERENCE_GENERATE_DESC') }}
			</p>
			<div
				class="tw-relative tw-mb-6 tw-mt-3 tw-max-h-dvh tw-overflow-scroll tw-rounded-coordinator tw-border tw-border-neutral-300"
			>
				<!-- header -->
				<div
					class="tw-sticky tw-top-0 tw-z-10 tw-grid tw-bg-neutral-100 tw-p-3"
					style="grid-template-columns: 20% repeat(3, minmax(0, 1fr))"
				>
					<label class="!tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_APPLICATION_APPLICANT') }}</label>
					<label class="!tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_ACTUAL_REFERENCE') }}</label>
					<label class="!tw-mb-0 tw-font-medium">{{ translate('COM_EMUNDUS_NEW_REFERENCE') }}</label>
				</div>

				<div>
					<div
						v-for="reference in references"
						class="tw-grid tw-p-3 hover:tw-bg-neutral-200"
						style="grid-template-columns: 20% repeat(3, minmax(0, 1fr))"
					>
						<label class="tw-mb-0">{{ reference.applicant }}</label>
						<label class="tw-mb-0 tw-whitespace-nowrap">{{ oldReference(reference) }}</label>
						<Reference class="tw-whitespace-nowrap" :reference="reference.new_reference" />
					</div>
				</div>
			</div>

			<div class="tw-flex tw-justify-end">
				<Button @click="saveReferences">
					{{ translate('COM_EMUNDUS_CUSTOM_REFERENCE_CONFIRM_SAVE') }}
				</Button>
			</div>
		</div>

		<Loader v-if="loading" />
	</div>
</template>

<style scoped></style>
