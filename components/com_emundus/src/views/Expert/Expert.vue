<script>
import TipTapEditor from 'tip-tap-editor';
import emailService from '@/services/email.js';
import Multiselect from 'vue-multiselect';

export default {
	name: 'Expert',
	components: { Multiselect, TipTapEditor },
	props: {},
	data: () => ({
		loading: false,
		reloadForm: 0,

		// props
		form: {
			fromName: '',
			from: '',
			emailId: 0,
			subject: '',
			to: [],
			body: '',
		},
		errors: {
			fromName: false,
			subject: false,
			to: false,
			body: false,
		},

		experts: [],
		emails: [],

		editorPlugins: [
			'history',
			'link',
			'image',
			'bold',
			'italic',
			'underline',
			'left',
			'center',
			'right',
			'h1',
			'h2',
			'ul',
		],
	}),
	created() {
		this.loading = true;

		this.getEmailModels().then(() => {
			this.getExpertConfig().then(() => {
				this.getExpertsList().then(() => {
					this.loading = false;
				});
			});
		});
	},
	methods: {
		/* SERVICES */
		async getExpertConfig() {
			return await emailService.getExpertConfig().then((response) => {
				if (response.status) {
					this.form.emailId = response.data.id;
					this.form.from = response.data.emailfrom;
					this.form.fromName = response.data.name;

					this.reloadForm++;

					return true;
				}
			});
		},

		async getEmailModels() {
			return await emailService.getEmails().then((response) => {
				if (response.status) {
					return (this.emails = response.data.datas);
				}
			});
		},

		async getExpertsList() {
			return await emailService.getExperts().then((response) => {
				if (response.status) {
					return (this.experts = response.data);
				}
			});
		},

		async sendExpertEmail() {
			if (this.form.fromName === '') {
				this.errors.fromName = true;
				this.$refs.expert_fromName.focus();
				return;
			}

			if (this.form.subject === '') {
				this.errors.subject = true;
				this.$refs.expert_subject.focus();
				return;
			}

			if (this.form.to.length === 0) {
				this.errors.to = true;
				this.$refs.expert_to.$el.focus();
				return;
			}

			if (this.form.body === '') {
				this.errors.body = true;
				this.$refs.expert_body.focus();
				return;
			}

			this.loading = true;

			this.form.to = this.form.to.map((expert) => expert.email);

			let data = {
				mail_from_name: this.form.fromName,
				mail_from: this.form.from,
				mail_to: this.form.to,
				mail_subject: this.form.subject,
				mail_body: this.form.body,
			};

			return await emailService.sendExpertEmail(data).then((response) => {
				if (response.status) {
					this.loading = false;

					Swal.fire({
						iconHtml:
							'<img class="tw-max-w-none" style="width: 180px;" src="/media/com_emundus/images/tchoozy/complex-illustrations/message-sent.svg"/>',
						title: this.translate('COM_EMUNDUS_EXPERT_MAIL_SENT_SUCCESS'),
						text: this.translate('COM_EMUNDUS_EXPERT_MAIL_SENT_SUCCESS_MESSAGE'),
						showCancelButton: false,
						showConfirmButton: false,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
							htmlContainer: '!tw-text-center',
						},
						timer: 3000,
					});
				}
			});
		},

		addOption(newOption) {
			let res = /^[\w.+-]+@([\w-]+\.)+[\w-]{2,}$/;
			if (res.test(newOption)) {
				let expert = {
					email: newOption,
				};
				this.experts.push(expert);
				this.form.to.push(expert);
			}
		},
	},
	watch: {
		'form.emailId': function (val) {
			if (val > 0) {
				let email = this.emails.find((email) => email.id === val);
				this.form.subject = email.subject;
				this.form.body = email.message;
			}
		},
	},
};
</script>

<template>
	<div class="tw-w-[85vw]">
		<div class="tw-flex tw-flex-col tw-gap-3" :key="reloadForm" v-show="!loading">
			<div>
				<label>{{ translate('COM_EMUNDUS_EXPERT_MAIL_FROM_NAME') }}</label>
				<input v-model="form.fromName" type="text" ref="expert_fromName" />
				<div v-if="errors.fromName">
					<span class="tw-text-red-500">{{ translate('COM_EMUNDUS_EXPERT_MAIL_FROM_NAME_ERROR') }}</span>
				</div>
			</div>

			<div>
				<label>{{ translate('COM_EMUNDUS_EXPERT_MAIL_FROM') }}</label>
				<input v-model="form.from" type="text" ref="expert_from" />
			</div>

			<div>
				<label>{{ translate('COM_EMUNDUS_EXPERT_MAIL_TEMPLATES') }}</label>
				<div class="tw-flex tw-items-center">
					<select v-model="form.emailId" class="tw-w-full" ref="expert_emailId">
						<option value="-1">
							{{ translate('COM_EMUNDUS_PLEASE_SELECT') }}
						</option>
						<option v-for="(email, index) in emails" :key="index" :value="email.id">
							{{ email.subject }}
						</option>
					</select>
				</div>
			</div>

			<div>
				<label>{{ translate('COM_EMUNDUS_EXPERT_MAIL_SUBJECT') }}</label>
				<input v-model="form.subject" type="text" ref="expert_subject" />
				<div v-if="errors.subject">
					<span class="tw-text-red-500">{{ translate('COM_EMUNDUS_EXPERT_MAIL_SUBJECT_ERROR') }}</span>
				</div>
			</div>

			<div>
				<label>{{ translate('COM_EMUNDUS_EXPERT_MAIL_TO') }}</label>
				<multiselect
					ref="expert_to"
					v-model="form.to"
					label="email"
					track-by="email"
					:options="experts"
					:multiple="true"
					:taggable="true"
					:placeholder="translate('COM_EMUNDUS_EXPERT_SEND_MAIL_TO')"
					:tagPlaceholder="translate('COM_EMUNDUS_EXPERT_SEND_MAIL_TO_TAG_ENTER')"
					select-label=""
					selected-label=""
					deselect-label=""
					@tag="addOption"
				>
					<template #noOptions>{{ translate('COM_EMUNDUS_EXPERT_SEND_MAIL_TO_EMPTY') }}</template>
				</multiselect>
				<div v-if="errors.to">
					<span class="tw-text-red-500">{{ translate('COM_EMUNDUS_EXPERT_MAIL_TO_ERROR') }}</span>
				</div>
			</div>

			<div>
				<label>{{ translate('COM_EMUNDUS_EXPERT_MAIL_BODY') }}</label>
				<tip-tap-editor
					id="expert_body"
					v-model="form.body"
					:upload-url="'/index.php?option=com_emundus&controller=settings&task=uploadmedia'"
					:editor-content-height="'30em'"
					:class="'tw-mt-1'"
					:locale="'fr'"
					:preset="'custom'"
					:plugins="editorPlugins"
					:toolbar-classes="['tw-bg-white']"
					:editor-content-classes="['tw-bg-white']"
				/>
				<div v-if="errors.body">
					<span class="tw-text-red-500">{{ translate('COM_EMUNDUS_EXPERT_MAIL_BODY_ERROR') }}</span>
				</div>
			</div>

			<div class="tw-flex tw-justify-end">
				<button @click="sendExpertEmail" type="button" class="tw-btn-primary tw-w-auto">
					{{ translate('COM_EMUNDUS_EXPERT_SEND') }}
				</button>
			</div>
		</div>

		<div v-if="loading" class="tw-flex tw-justify-center">
			<div class="em-loader"></div>
		</div>
	</div>
</template>

<style scoped></style>
