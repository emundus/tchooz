<template>
	<div id="form-builder-rules-add" class="tw-w-full tw-self-start">
		<div class="tw-p-8">
			<div class="tw-flex tw-flex-col tw-gap-3">
				<div
					class="tw-mb-2 tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-gap-1"
					:title="translate('COM_EMUNDUS_FORM_BUILDER_RULE_GO_BACK')"
					@click="$emit('close-rule-add')"
				>
					<span class="material-symbols-outlined">chevron_left</span>
					<p>{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_GO_BACK') }}</p>
				</div>

				<form-builder-rules-js
					v-if="type === 'js' && elements.length > 0"
					:page="fabrikPage"
					:elements="elements"
					:rule="rule"
					@close-rule-add-js="$emit('close-rule-add')"
				/>
			</div>
		</div>
		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import formService from '../../../services/form';

import formBuilderMixin from '../../../mixins/formbuilder';
import globalMixin from '../../../mixins/mixin';
import errorMixin from '../../../mixins/errors';
import Swal from 'sweetalert2';
import FormBuilderRulesJs from '@/components/FormBuilder/FormBuilderRules/FormBuilderRulesType/FormBuilderRulesJs.vue';

export default {
	components: { FormBuilderRulesJs },
	props: {
		page: {
			type: Object,
			default: {},
		},
		mode: {
			type: String,
			default: 'forms',
		},
		type: {
			type: String,
			default: 'js',
		},
		rule: {
			type: Object,
			default: {},
		},
	},
	mixins: [formBuilderMixin, globalMixin, errorMixin],
	data() {
		return {
			fabrikPage: {},
			elements: [],

			loading: false,
		};
	},
	mounted() {
		if (this.page.id) {
			this.loading = true;

			formService.getPageObject(this.page.id).then((response) => {
				if (response.status && response.data != '') {
					this.fabrikPage = response.data;
				} else {
					this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
				}

				Object.entries(this.fabrikPage.Groups).forEach(([key, group]) => {
					Object.entries(group.elements).forEach(([key, element]) => {
						if (!element.hidden) {
							this.elements.push(element);
						}
					});
				});

				this.loading = false;
			});
		}
	},
	methods: {},
};
</script>
