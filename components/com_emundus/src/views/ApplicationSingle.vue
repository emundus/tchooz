<template>
	<modal
		v-show="showModal"
		:click-to-close="false"
		id="application-modal"
		name="application-modal"
		:height="'100vh'"
		ref="modal"
		v-if="selectedFile !== null && selectedFile !== undefined"
		:class="{ 'context-files': context === 'files', hidden: hidden }"
	>
		<div class="em-modal-header tw-flex tw-w-full tw-items-center tw-bg-profile-full tw-px-3 tw-py-4">
			<div class="tw-flex tw-w-full tw-items-center tw-justify-between" id="evaluation-modal-close">
				<div class="tw-flex tw-items-center tw-gap-2">
					<div @click="onClose" class="tw-flex tw-w-max tw-cursor-pointer tw-items-center">
						<span class="material-symbols-outlined tw-text-base" style="color: white">navigate_before</span>
						<span class="tw-ml-2 tw-text-sm tw-text-neutral-900 tw-text-white">{{ translate('BACK') }}</span>
					</div>
					<span class="tw-text-white">|</span>
					<p class="tw-text-sm" style="color: white" v-if="selectedFile.applicant_name != ''">
						{{ selectedFile.applicant_name }} - {{ selectedFile.fnum }}
					</p>
					<p class="tw-text-sm" style="color: white" v-else>
						{{ selectedFile.fnum }}
					</p>
				</div>
				<div v-if="fnums.length > 1" class="tw-flex tw-items-center">
					<span
						class="material-symbols-outlined tw-cursor-pointer tw-text-base"
						style="color: white"
						@click="openPreviousFnum"
						>navigate_before</span
					>
					<span
						class="material-symbols-outlined tw-cursor-pointer tw-text-base"
						style="color: white"
						@click="openNextFnum"
						>navigate_next</span
					>
				</div>
			</div>
		</div>

		<div
			class="modal-grid"
			style="height: calc(100% - 56px)"
			:style="'grid-template-columns:' + this.ratioStyle"
			v-if="access"
		>
			<div id="modal-applicationform">
				<div class="scrollable" style="height: calc(100vh - 56px)">
					<div
						class="sticky-tab em-bg-neutral-100 tw-flex tw-items-center tw-justify-center tw-gap-4 tw-border-b tw-border-neutral-300"
						style="z-index: 2"
					>
						<div
							v-for="tab in tabsICanAccessTo"
							:key="tab.name"
							class="em-light-tabs tw-cursor-pointer"
							@click="selected = tab.name"
							:class="selected === tab.name ? 'em-light-selected-tab' : ''"
						>
							<span class="tw-text-sm">{{ translate(tab.label) }}</span>
						</div>
					</div>

					<div v-if="!loading">
						<div v-for="tab in tabs" :key="tab.name">
							<div v-if="tab.name === 'application' && selected === 'application'" v-html="applicationform"></div>
							<Attachments
								v-if="tab.name === 'attachments' && selected === 'attachments'"
								:fnum="selectedFile.fnum"
								:user="$props.user"
								:columns="['check', 'name', 'date', 'category', 'status']"
								:displayEdit="false"
								:key="selectedFile.fnum"
							/>
							<Comments
								v-if="tab.name === 'comments' && selected === 'comments'"
								:fnum="selectedFile.fnum"
								:user="$props.user"
								:access="access['10']"
								:key="selectedFile.fnum"
							/>
							<Messages
								v-if="tab.name === 'messenger' && selected === 'messenger'"
								:fnum="selectedFile.fnum"
								:fullname="$props.fullname"
								:applicant="$props.applicant"
							/>
							<Synthesis
								v-if="tab.name === 'synthesis' && selected === 'synthesis'"
								:fnum="selectedFile.fnum"
								:content="filesSynthesis[selectedFile.fnum]"
							>
							</Synthesis>
							<div v-if="tab.type && tab.type === 'iframe' && selected === tab.name">
								<iframe :id="tab.name" :src="replaceTagsIframeUrl(tab.url)" class="tw-h-screen tw-w-full"></iframe>
							</div>

							<evaluation-list
								v-if="tab.type && tab.type === 'evaluation-list' && selected === tab.name"
								:step="tab.step"
								:ccid="this.ccid"
							>
							</evaluation-list>
						</div>
					</div>
				</div>
			</div>

			<Evaluations
				v-if="selectedFile"
				:fnum="typeof selectedFile === 'string' ? selectedFile : selectedFile.fnum"
				:key="typeof selectedFile === 'string' ? selectedFile : selectedFile.fnum"
				:defaultCcid="ccid"
			>
			</Evaluations>
		</div>
	</modal>
</template>

<script>
import axios from 'axios';
import Attachments from '@/views/Attachments.vue';
import Evaluations from '@/components/Files/Evaluations.vue';
import filesService from '@/services/files.js';
import errors from '@/mixins/errors.js';
import Comments from '@/views/Comments.vue';
import Modal from '@/components/Modal.vue';
import evaluationService from '@/services/evaluation.js';
import fileService from '@/services/file.js';
import EvaluationList from '@/components/Files/EvaluationList.vue';
import Messages from './Messenger/Messages.vue';
import Synthesis from '@/components/Files/Synthesis.vue';

export default {
	name: 'ApplicationSingle',
	components: {
		Synthesis,
		Messages,
		EvaluationList,
		Comments,
		Attachments,
		Modal,
		Evaluations,
	},
	props: {
		file: Object | String,
		type: String,
		user: {
			type: String,
			required: true,
		},
		ratio: {
			type: String,
			default: '66/33',
		},
		context: {
			type: String,
			default: '',
		},
		defaultTabs: {
			type: Array,
			default: () => [],
		},
		fullname: {
			type: String,
			required: true,
		},
		applicant: {
			type: Boolean,
			default: false,
		},
	},
	mixins: [errors],
	data: () => ({
		showModal: true,
		fnums: [],
		selectedFile: null,
		applicationform: '',
		selected: 'application',
		tabs: [
			{
				label: 'COM_EMUNDUS_FILES_APPLICANT_FILE',
				name: 'application',
				access: '1',
			},
			{
				label: 'COM_EMUNDUS_FILES_ATTACHMENTS',
				name: 'attachments',
				access: '4',
			},
			{
				label: 'COM_EMUNDUS_FILES_COMMENTS',
				name: 'comments',
				access: '10',
			},
			{
				label: 'COM_EMUNDUS_FILES_MESSENGER',
				name: 'messenger',
				access: '36',
			},
			{
				label: 'COM_EMUNDUS_APPLICATION_SYNTHESIS',
				name: 'synthesis',
				access: '1',
			},
		],
		ccid: 0,
		url: null,
		access: null,
		student_id: null,
		hidden: false,
		loading: false,
		filesSynthesis: {},
	}),

	created() {
		if (this.defaultTabs.length > 0) {
			this.tabs = this.defaultTabs;
			// set the first tab as selected
			this.selected = this.defaultTabs[0].name;
		}

		if (document.querySelector('body.layout-evaluation')) {
			document.querySelector('body.layout-evaluation').style.overflow = 'hidden';
		}

		const r = document.querySelector(':root');
		let ratio_array = this.$props.ratio.split('/');
		r.style.setProperty('--attachment-width', ratio_array[0] + '%');

		this.selectedFile = this.file;

		// if props file is not null, then render
		if (typeof this.selectedFile !== 'undefined' && this.selectedFile !== null) {
			this.render();
		} else {
			// Check if we find a hash in the URL
			const hash = window.location.hash;
			if (hash) {
				this.selectedFile = hash.replace('#', '');
				this.render();
			} else {
				this.showModal = false;
			}
		}

		this.addEventListeners();
	},
	onBeforeDestroy() {
		window.removeEventListener('openSingleApplicationWithFnum');
	},

	methods: {
		addEventListeners() {
			window.addEventListener('openSingleApplicationWithFnum', (e) => {
				this.showModal = true;
				if (e.detail.fnum) {
					this.selectedFile = e.detail.fnum;
				}

				if (e.detail.fnums) {
					this.fnums = e.detail.fnums;
				}

				if (typeof this.selectedFile !== 'undefined' && this.selectedFile !== null) {
					this.render();
					if (this.$refs['modal']) {
						this.$refs['modal'].open();
					}
				}
			});
		},
		getSynthesis(fnum) {
			fileService
				.getFileSynthesis(fnum)
				.then((response) => {
					if (response.data.length == 0) {
						this.tabs = this.tabs.filter((tab) => tab.name !== 'synthesis');
					} else {
						// re add the synthesis tab if it was removed
						if (!this.tabs.find((tab) => tab.name === 'synthesis')) {
							this.tabs.push({
								label: 'COM_EMUNDUS_APPLICATION_SYNTHESIS',
								name: 'synthesis',
								access: '1',
							});
						}

						this.filesSynthesis[this.selectedFile.fnum] = response.data;
					}
				})
				.catch((error) => {
					console.error('Error fetching synthesis:', error);
				});
		},
		render() {
			this.loading = true;
			let fnum = '';

			if (typeof this.selectedFile == 'string') {
				fnum = this.selectedFile;
			} else {
				fnum = this.selectedFile.fnum;
			}

			this.getSynthesis(fnum);

			if (typeof this.selectedFile == 'string') {
				filesService.getFile(fnum, this.$props.type).then((result) => {
					if (result.status == 1) {
						this.selectedFile = result.data;
						this.access = result.rights;

						if (this.defaultTabs.length > 0) {
							this.selected = this.defaultTabs[0].name;
						} else {
							this.selected = 'application';
						}
						this.updateURL(this.selectedFile.fnum);
						this.getApplicationForm();
						this.getReadonlyEvaluations();

						this.showModal = true;
						this.hidden = false;
						this.loading = false;
					} else {
						this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS', result.msg).then((confirm) => {
							if (confirm === true) {
								this.showModal = false;
								this.hidden = true;
							}
						});
						this.loading = false;
					}
				});
			} else {
				filesService
					.checkAccess(fnum)
					.then((result) => {
						if (result.status == true) {
							this.access = result.data;
							this.updateURL(this.selectedFile.fnum);
							if (this.access['1'].r) {
								this.getApplicationForm();
							} else {
								if (this.access['4'].r) {
									this.selected = 'attachments';
								} else if (this.access['10'].r) {
									this.selected = 'comments';
								}
							}

							this.getReadonlyEvaluations();

							this.showModal = true;
							this.hidden = false;
						} else {
							this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS', 'COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC').then(
								(confirm) => {
									if (confirm === true) {
										this.showModal = false;
										this.hidden = true;
									}
								},
							);
						}
					})
					.catch((error) => {
						this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS', 'COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC');
						this.loading = false;
					});
			}
		},

		getApplicationForm() {
			axios({
				method: 'get',
				url:
					'index.php?option=com_emundus&view=application&format=raw&layout=form&fnum=' +
					this.selectedFile.fnum +
					'&context=modal',
			}).then((response) => {
				this.applicationform = response.data;
				if (this.$props.type !== 'evaluation') {
					this.loading = false;
				}
			});
		},
		getReadonlyEvaluations() {
			const fnum = typeof this.selectedFile === 'string' ? this.selectedFile : this.selectedFile.fnum;

			fileService.getFileIdFromFnum(fnum).then((response) => {
				if (response.status) {
					this.ccid = response.data;

					evaluationService
						.getEvaluationsForms(fnum, true)
						.then((response) => {
							response.data.forEach((step) => {
								this.access[step.action_id] = {
									r: true,
									c: false,
								};

								// check if the tab already exists
								if (this.tabs.find((tab) => tab.name === 'step-' + step.id)) {
									return;
								}

								if (step.url) {
									this.tabs.push({
										label: step.label,
										name: 'step-' + step.id,
										access: step.action_id,
										type: 'iframe',
										url: step.url,
									});
								} else if (step.multiple) {
									this.tabs.push({
										label: step.label,
										name: 'step-' + step.id,
										access: step.action_id,
										type: 'evaluation-list',
										step: step,
									});
								}
							});
						})
						.catch((error) => {
							console.log(error);
						});
				}
			});
		},
		updateURL(fnum = '') {
			let url = window.location.href;
			url = url.split('#');

			if (fnum === '') {
				window.history.pushState('', '', url[0]);
			} else {
				window.history.pushState('', '', url[0] + '#' + fnum);
			}
		},
		onClose(e) {
			e.preventDefault();
			this.hidden = true;
			this.showModal = false;
			document.querySelector('body').style.overflow = 'visible';

			// Remove the hash from the URL
			this.updateURL();

			window.postMessage('reloadData');
		},
		openNextFnum() {
			let index =
				typeof this.selectedFile === 'string'
					? this.fnums.indexOf(this.selectedFile)
					: this.fnums.indexOf(this.selectedFile.fnum);
			if (index !== -1 && index < this.fnums.length - 1) {
				const newIndex = index + 1;
				if (newIndex > this.fnums.length) {
					this.selectedFile = this.fnums[0];
				} else {
					this.selectedFile = this.fnums[newIndex];
				}

				this.render();
			}
		},
		openPreviousFnum() {
			let index =
				typeof this.selectedFile === 'string'
					? this.fnums.indexOf(this.selectedFile)
					: this.fnums.indexOf(this.selectedFile.fnum);

			if (index !== -1 && index > 0) {
				const newIndex = index - 1;
				if (newIndex < 0) {
					// open last fnum
					this.selectedFile = this.fnums[this.fnums.length - 1];
				} else {
					this.selectedFile = this.fnums[newIndex];
				}
				this.render();
			}
		},
		replaceTagsIframeUrl(url) {
			return url.replace('{fnum}', this.selectedFile.fnum);
		},
	},
	computed: {
		ratioStyle() {
			let ratio_array = this.$props.ratio.split('/');
			return ratio_array[0] + '% ' + ratio_array[1] + '%';
		},
		tabsICanAccessTo() {
			return this.tabs.filter((tab) => this.access[tab.access].r || this.access[tab.access].c);
		},
	},
};
</script>

<style>
.modal-grid {
	display: grid;
	grid-gap: 16px;
	width: 100%;
	height: auto;
}

.scrollable {
	height: calc(100vh - 100px);
	overflow-y: scroll;
	overflow-x: hidden;
}

.em-container-form-heading {
	display: none;
}

#iframe {
	height: 100vh;
	overflow-y: scroll;
	overflow-x: hidden;
}

.iframe-evaluation {
	width: 100%;
	height: calc(100% - 124px);
	border: unset;
}

#modal-evaluationgrid {
	border-left: 1px solid #ebecf0;
	box-shadow: 0 4px 16px rgba(32, 35, 44, 0.1);
}

.sticky-tab {
	position: sticky;
	top: 0;
	background: white;
}

#modal-applicationform #em-attachments #edit-modal {
	width: var(--attachment-width) !important;
	top: 52px;
	height: calc(100% - 52px) !important;
}

#modal-applicationform #em-attachments .modal-body {
	width: 100%;
}

#modal-applicationform #em-attachments #em-attachment-preview {
	width: 100%;
}

.context-files:not(.hidden) {
	position: fixed;
	top: 0;
	left: 0;
	background-color: white;
	z-index: 9999;
	width: 100vw;
	height: 100vh;
	opacity: 1;
}

.hidden {
	display: none;
	z-index: -1;
	margin: 0;
	padding: 0;
	width: 0;
	height: 0;
}
</style>
