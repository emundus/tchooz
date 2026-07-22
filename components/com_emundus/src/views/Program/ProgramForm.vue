<template>
	<div
		id="program-edition-container"
		:class="
			newProgram
				? 'em-card-shadow tw-m-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6'
				: ''
		"
	>
		<Back v-if="!program" :link="'index.php?option=com_emundus&view=campaigns'" />

		<div class="tw-mt-4">
			<h1>
				{{
					newProgram
						? translate('COM_EMUNDUS_PROGRAM_FORM_CREATE_TITLE')
						: translate('COM_EMUNDUS_PROGRAM_FORM_EDIT_TITLE')
				}}
			</h1>

			<hr />

			<ParameterForm id="program-form" ref="parameterForm" :groups="formGroups" @parameter-value-updated="cleanSlug" />

			<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
				<Button @click="save">
					{{ newProgram ? translate('COM_EMUNDUS_PROGRAM_FORM_CREATE') : translate('COM_EMUNDUS_PROGRAM_FORM_EDIT') }}
				</Button>
			</div>
		</div>
	</div>
</template>

<script>
import Multiselect from 'vue-multiselect';
import Tabs from '@/components/Utils/Tabs.vue';
import Back from '@/components/Utils/Back.vue';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import Button from '@/components/Atoms/Button.vue';
import programService from '@/services/programme.js';

import parameterForm from '@/mixins/parameterForm.js';
import alerts from '@/mixins/alerts.js';
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';
export default {
	name: 'ProgramForm',
	components: { Button, ParameterForm, Back, Tabs, Multiselect },
	mixins: [parameterForm, alerts],
	props: {
		program: {
			type: Object,
			required: true,
		},
		prestation_sociales: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data() {
		return {
			formGroups: [
				{
					id: 'default-group',
					title: '',
					description: '',
					helpTextType: 'above',
					parameters: [
						{
							param: 'label',
							type: 'text',
							placeholder: '',
							value: '',
							label: 'COM_EMUNDUS_PROGRAM_LABEL_LABEL',
							displayed: true,
						},
						{
							param: 'code',
							type: 'text',
							placeholder: '',
							value: '',
							label: 'COM_EMUNDUS_PROGRAM_CODE_LABEL',
							displayed: true,
						},
						{
							param: 'programmes',
							type: 'autocomplete',
							placeholder: '',
							value: '',
							label: 'COM_EMUNDUS_PROGRAM_PROGRAMMES_LABEL',
							helptext: '',
							displayed: true,
							autocompleteItems: [],
							optional: true,
						},
						{
							param: 'notes',
							type: 'wysiwig',
							value: '',
							label: 'COM_EMUNDUS_PROGRAM_DESCRIPTION_LABEL',
							displayed: true,
							preset: 'full',
							optional: true,
						},
						{
							param: 'long_description',
							type: 'wysiwig',
							placeholder: '',
							value: '',
							label: 'COM_EMUNDUS_PROGRAM_LONG_DESCRIPTION_LABEL',
							helptext: 'COM_EMUNDUS_PROGRAM_LONG_DESCRIPTION_HELPTEXT',
							displayed: this.prestation_sociales,
							preset: 'full',
							optional: true,
						},
						{
							param: 'synthesis',
							type: 'wysiwig',
							value: '<p><strong>[APPLICANT_NAME]</strong></p>\n<p><a href="mailto:[EMAIL]">[EMAIL]</a></p>',
							label: 'COM_EMUNDUS_PROGRAM_SYNTHESIS_LABEL',
							helptext: 'COM_EMUNDUS_PROGRAM_SYNTHESIS_HELP',
							displayed: true,
							preset: 'full',
							displaySuggestions: true,
							optional: true,
						},
						{
							param: 'logo',
							type: 'file',
							fileUrl: window.location.origin + '/index.php?option=com_emundus&task=programme.uploadLogo',
							value: '',
							label: 'COM_EMUNDUS_PROGRAM_LOGO_LABEL',
							displayed: true,
							optional: true,
						},
						{
							param: 'apply_online',
							type: 'toggle',
							placeholder: '',
							value: '1',
							label: 'COM_EMUNDUS_PROGRAM_APPLY_ONLINE_LABEL',
							hideLabel: true,
							displayed: true,
						},
						{
							param: 'must_open_rights',
							type: 'toggle',
							placeholder: '',
							value: '0',
							label: 'COM_EMUNDUS_PROGRAM_MUST_OPEN_RIGHTS_LABEL',
							hideLabel: true,
							displayed: this.prestation_sociales,
						},
					],
					isRepeatable: false,
				},
			],

			codeUpdated: false,
			lastAutoCode: null,
		};
	},
	created() {
		if (this.program && this.program.id > 0) {
			this.codeUpdated = true;

			this.fillFormGroupsFromObject(this.formGroups, this.program);
		}
		this.getProgramCategories();
	},
	methods: {
		getProgramCategories() {
			programService.getProgramCategories().then((response) => {
				this.formGroups[0].parameters.find((p) => p.param === 'programmes').autocompleteItems = response.data.map(
					(program) => {
						return program.label;
					},
				);
			});
		},

		save() {
			const { isValid, form: program_form } = this.validateParameterForm(this.$refs.parameterForm);
			if (!isValid) return;

			program_form.code = this.slugify(program_form.code);

			if (this.program.id && this.program.id > 0) {
				program_form.id = this.program.id;
			}

			this.loading = true;
			programService.saveProgram(program_form, true).then((response) => {
				if (response.status) {
					this.alertSuccess(response.msg).then(() => {
						settingsService.redirectJRoute(
							'index.php?option=com_emundus&view=campaigns',
							useGlobalStore().getCurrentLang,
						);
					});
				} else {
					this.loading = false;
					this.alertError(response.message);
				}
			});
		},

		cleanSlug(parameter) {
			if (!parameter || !parameter.param) return;
			if (parameter.param !== 'label' && parameter.param !== 'code') return;

			const codeField = this.formGroups[0].parameters.find((p) => p.param === 'code');
			if (!codeField) return;

			if (parameter.param === 'label') {
				if (!this.codeUpdated) {
					const slug = this.slugify(parameter.value);
					codeField.value = slug;
					this.lastAutoCode = slug;
					// Write to the Parameter's internal `value` via its ref so the input
					// updates without remounting (a `reload` bump would steal focus and
					// re-emit `valueUpdated`, which would flip `codeUpdated` and kill the auto-fill).
					const fieldRef = this.$refs.parameterForm?.$refs?.['field_code'];
					if (fieldRef && fieldRef[0]) {
						fieldRef[0].value = slug;
					}
				}
				return;
			}

			// Echo from our own auto-fill — ignore so the user's `label` typing keeps driving `code`.
			if (this.lastAutoCode !== null && parameter.value === this.lastAutoCode) {
				this.lastAutoCode = null;
				return;
			}

			// User typed in the `code` field directly: stop auto-filling from `label`.
			if (parameter.value !== '') {
				this.codeUpdated = true;
			}

			const slugified = this.slugify(parameter.value);
			if (parameter.value !== slugified) {
				const fieldRef = this.$refs.parameterForm?.$refs?.['field_code'];
				if (fieldRef && fieldRef[0]) {
					fieldRef[0].value = slugified;
				}
			}
		},

		/**
		 * Replace spaces with `-`, strip every other non-alphanumeric character,
		 * truncate to 10 chars, lowercase.
		 *
		 * @param {*} input
		 * @returns {string}
		 */
		slugify(input) {
			if (typeof input !== 'string') return '';
			return input
				.replace(/ /g, '-')
				.replace(/[^A-Za-z0-9-]/g, '')
				.substring(0, 20)
				.toLowerCase();
		},
	},
	computed: {
		newProgram() {
			return !this.program || this.program.id == 0;
		},
	},
};
</script>

<style></style>
