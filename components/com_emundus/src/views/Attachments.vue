<template>
	<div id="em-attachments" class="tw-w-full">
		<div class="wrapper" :class="{ loading: loading }">
			<section id="filters" class="tw-flex tw-items-center tw-justify-between">
				<div class="tw-flex tw-items-center">
					<div class="searchbar-wrapper tw-flex tw-items-center">
						<input
							id="searchbar"
							type="text"
							ref="searchbar"
							:placeholder="translate('COM_EMUNDUS_ACTIONS_SEARCH')"
							v-model="search"
							@input="onSearch"
							@keyup.enter="onSearchKeyup"
						/>
						<span class="material-symbols-outlined search">search</span>
						<span class="material-symbols-outlined clear tw-cursor-pointer" @click="search = ''">clear</span>
					</div>
					<select
						v-if="columns.includes('category') && Object.entries(displayedAttachmentCategories).length > 0"
						name="category"
						class="category-select em-ml-16"
						v-model="category"
					>
						<option value="all">
							{{ translate('COM_EMUNDUS_ATTACHMENTS_SELECT_CATEGORY') }}
						</option>
						<option v-for="(category, key) in displayedAttachmentCategories" :key="key" :value="key">
							{{ category }}
						</option>
					</select>
				</div>
				<div class="actions tw-flex tw-items-center">
					<div
						v-if="canExport"
						class="btn-icon-text"
						@click="exportAttachments"
						:class="{ disabled: checkedAttachments.length < 1 }"
					>
						<span class="material-symbols-outlined export tw-mr-2">file_upload</span>
						<span>{{ translate('COM_EMUNDUS_EXPORTS_EXPORT') }}</span>
					</div>
					<div
						v-if="sync && canSync"
						class="btn-icon-text"
						@click="synchronizeAttachments(checkedAttachments)"
						:class="{ disabled: checkedAttachments.length < 1 }"
					>
						<span class="material-symbols-outlined cloud_sync" :title="translate('COM_EMUNDUS_ATTACHMENTS_SYNC_TITLE')">
							cloud_sync
						</span>
						<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_SYNC_TITLE') }}</span>
					</div>
					<span
						class="material-symbols-outlined refresh tw-cursor-pointer"
						@click="refreshAttachments(true)"
						:title="translate('COM_EMUNDUS_ATTACHMENTS_REFRESH_TITLE')"
					>
						autorenew
					</span>
					<span
						v-if="canDelete"
						class="material-symbols-outlined delete"
						:class="{ disabled: checkedAttachments.length < 1 }"
						@click="confirmDeleteAttachments"
						:title="translate('COM_EMUNDUS_ATTACHMENTS_DELETE_TITLE')"
					>
						delete
					</span>
				</div>
			</section>
			<div v-if="exportLink" class="tw-mb-4 tw-mt-4">
				<a :href="exportLink" target="_blank" @click="exportLink = ''">
					{{ translate('COM_EMUNDUS_ATTACHMENTS_EXPORT_LINK') }}
				</a>
			</div>
			<section v-if="attachments.length" class="table-wrapper tw-w-full">
				<table :class="{ loading: loading }" aria-describedby="Table of attachments information">
					<thead>
						<tr>
							<th v-if="columns.includes('check')" id="check-th">
								<input class="attachment-check" type="checkbox" @change="updateAllCheckedAttachments" />
							</th>
							<th v-if="columns.includes('name')" id="name" @click="orderBy('value')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_NAME') }}</span>
								<span
									v-if="sort.orderBy === 'value' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'value' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="columns.includes('date')" id="date" class="date" @click="orderBy('timedate')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_SEND_DATE') }}</span>
								<span
									v-if="sort.orderBy === 'timedate' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'timedate' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="columns.includes('desc')" id="desc" class="desc" @click="orderBy('upload_description')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_DESCRIPTION') }}</span>
								<span
									v-if="sort.orderBy === 'upload_description' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'upload_description' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="columns.includes('category')" id="category" class="category" @click="orderBy('category')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_CATEGORY') }}</span>
								<span
									v-if="sort.orderBy === 'category' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'category' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="columns.includes('status')" id="status" class="status" @click="orderBy('is_validated')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_CHECK') }}</span>
								<span
									v-if="sort.orderBy === 'is_validated' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'is_validated' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="canSee && columns.includes('user')" id="user" @click="orderBy('user_id')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_UPLOADED_BY') }}</span>
								<span
									v-if="sort.orderBy === 'user_id' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'user_id' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="canSee && columns.includes('modified_by')" id="modified_by" @click="orderBy('modified_by')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_MODIFIED_BY') }}</span>
								<span
									v-if="sort.orderBy === 'modified_by' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'modified_by' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="columns.includes('modified')" id="modified" class="date" @click="orderBy('modified')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_MODIFICATION_DATE') }}</span>
								<span
									v-if="sort.orderBy === 'modified' && sort.order === 'asc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_upward</span
								>
								<span
									v-if="sort.orderBy === 'modified' && sort.order === 'desc'"
									class="material-symbols-outlined tw-text-base"
									>arrow_downward</span
								>
							</th>
							<th v-if="columns.includes('permissions')" id="permissions" class="permissions">
								{{ translate('COM_EMUNDUS_ATTACHMENTS_PERMISSIONS') }}
							</th>
							<th v-if="sync && columns.includes('sync')" id="sync" class="sync" @click="orderBy('sync')">
								<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_SYNC') }}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						<AttachmentRow
							v-for="attachment in displayedAttachments"
							:key="attachment.aid"
							:ref="'attachment-row-' + attachment.aid"
							:attachment="attachment"
							:checkedAttachmentsProp="checkedAttachments"
							:canUpdate="canUpdate"
							:sync="sync"
							:canSee="canSee"
							@open-modal="openModal(attachment)"
							@update-status="updateStatus"
							@change-permission="changePermission"
							:columns="$props.columns"
							:is_applicant="$props.is_applicant"
						>
						</AttachmentRow>
					</tbody>
				</table>
			</section>
			<div v-else class="tw-text-center">
				<img
					src="@media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg"
					alt="empty-list"
					style="width: 10vw; height: 10vw; margin: 0 auto"
				/>
				<p>{{ translate('COM_EMUNDUS_ATTACHMENTS_NO_ATTACHMENTS_FOUND') }}</p>
			</div>
			<section id="add-document-section" class="tw-mt-4 tw-flex tw-items-center" v-if="this.canCreate">
				<button class="em-w-auto tw-btn-primary" @click="addAttachment">
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_NEW_DOCUMENT') }}
				</button>
			</section>
		</div>

		<div v-show="openedModal">
			<modal
				id="edit-modal"
				name="edit"
				:resizable="true"
				:draggable="true"
				@closed="closeModal"
				:click-to-close="false"
			>
				<div class="modal-head tw-flex tw-w-full tw-items-center tw-justify-between">
					<div id="actions-left" class="tw-flex tw-items-center tw-justify-start">
						<span>{{ selectedAttachment.filename }}</span>
					</div>
					<div id="actions-right" class="tw-flex tw-items-center">
						<a
							v-if="sync && syncSelectedPreview"
							class="download btn-icon-text tw-mr-6"
							:href="syncSelectedPreview"
							target="_blank"
						>
							<span class="material-symbols-outlined">open_in_new</span>
							<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_OPEN_IN_GED') }}</span>
						</a>
						<a download v-if="canDownload" :href="attachmentPath" class="download btn-icon-text tw-mr-6">
							<span class="material-symbols-outlined"> file_download </span>
							<span>{{ translate('COM_EMUNDUS_ATTACHMENTS_LINK_TO_DOWNLOAD') }}</span>
						</a>
						<div class="prev-next-attachments tw-mr-2 tw-flex tw-items-center tw-justify-between">
							<div
								class="prev tw-mr-1 tw-flex tw-items-center"
								:class="{ active: selectedAttachmentPosition > 0 }"
								@click="changeAttachment(selectedAttachmentPosition - 1, true)"
							>
								<span class="material-symbols-outlined"> navigate_before </span>
							</div>
							<span class="lvl">{{ selectedAttachmentPosition + 1 }} /{{ displayedAttachments.length }}</span>
							<div
								class="next tw-ml-1 tw-flex tw-items-center"
								:class="{
									active: selectedAttachmentPosition < displayedAttachments.length - 1,
								}"
								@click="changeAttachment(selectedAttachmentPosition + 1)"
							>
								<span class="material-symbols-outlined"> navigate_next </span>
							</div>
						</div>
						<span class="material-symbols-outlined tw-cursor-pointer" @click="closeModal">close</span>
					</div>
				</div>
				<transition :name="slideTransition" @before-leave="beforeLeaveSlide">
					<div
						v-if="!modalLoading && displayedUser.user_id && displayedFnum"
						class="modal-body tw-flex tw-items-center"
						:class="{ 'only-preview': onlyPreview }"
					>
						<AttachmentPreview
							v-if="openedModal"
							@fileNotFound="canDownload = false"
							@canDownload="canDownload = true"
							:user="displayedUser.user_id"
							:default-attachment="selectedAttachment"
						></AttachmentPreview>
						<AttachmentEdit
							v-if="displayEdit && openedModal"
							:fnum="displayedFnum"
							:columns="$props.columns"
							:is_applicant="$props.is_applicant"
							:is-displayed="!onlyPreview"
							@closeModal="closeModal"
							@saveChanges="updateAttachment"
							@update-displayed="toggleOnlyPreview"
						></AttachmentEdit>
					</div>
				</transition>
			</modal>
		</div>
		<div class="vue-em-loader em-loader" v-if="loading"></div>
	</div>
</template>

<script>
import AttachmentPreview from '@/components/Attachments/AttachmentPreview.vue';
import AttachmentEdit from '@/components/Attachments/AttachmentEdit.vue';
import AttachmentRow from '@/components/Attachments/AttachmentRow.vue';
import Modal from '@/components/Modal.vue';

import attachmentService from '@/services/attachment.js';
import userService from '@/services/user.js';
import fileService from '@/services/file.js';
import syncService from '@/services/sync.js';

import mixin from '../mixins/mixin.js';
import Swal from 'sweetalert2';

import { useAttachmentStore } from '@/stores/attachment.js';
import { useGlobalStore } from '@/stores/global.js';
import { useUserStore } from '@/stores/user.js';

export default {
	name: 'Attachments',
	components: {
		AttachmentPreview,
		AttachmentEdit,
		AttachmentRow,
		Modal,
	},
	props: {
		user: {
			type: String,
			required: true,
		},
		fnum: {
			type: String,
			required: true,
		},
		defaultAttachments: {
			type: Array,
			default: null,
		},
		defaultRights: {
			type: Object,
			default: null,
		},
		columns: {
			type: Array,
			default() {
				return [
					'check',
					'name',
					'date',
					'desc',
					'category',
					'status',
					'user',
					'modified_by',
					'modified',
					'permissions',
					'sync',
				];
			},
		},
		is_applicant: {
			type: String,
			default: null,
		},
		displayEdit: {
			type: Boolean,
			default: true,
		},
	},
	mixins: [mixin],
	data() {
		return {
			loading: true,
			attachments: [],
			categories: {},
			fnums: [],
			users: [],
			displayedUser: {},
			displayedFnum: this.fnum,
			fnumInfos: null,
			checkedAttachments: [],
			selectedAttachment: {},
			progress: '',
			sort: { last: '', order: '', orderBy: '' },
			canSee: true,
			canExport: false,
			canCreate: false,
			canDelete: false,
			canDownload: true,
			canUpdate: false,
			canSync: false,
			modalLoading: false,
			slideTransition: 'slide-fade',
			onlyPreview: false,
			changeFileEvent: null,
			sync: false,
			syncSelectedPreview: null,
			exportLink: '',
			search: '',
			category: 'all',
			attachmentStore: null,

			openedModal: false,
		};
	},
	created() {
		this.attachmentStore = useAttachmentStore();
		const globalStore = useGlobalStore();
		const userStore = useUserStore();

		this.canSee = !globalStore.isAnonyme;
		userStore.setCurrentUser(this.user);

		syncService
			.isSyncModuleActive()
			.then((response) => {
				this.sync = response.data;
			})
			.catch((error) => {
				console.log(error);
				this.sync = false;
			});
	},
	mounted() {
		this.loading = true;

		this.setDisplayedUser().then(() => {
			if (this.defaultAttachments !== null) {
				this.getAttachmentCategories().then((response) => {
					this.categories = response ? response : {};
					this.attachments = this.defaultAttachments;
					this.attachmentStore.setAttachmentsOfFnum(this.displayedFnum, this.attachments);

					this.setAccessRights().then(() => {
						this.loading = false;
					});
				});
			} else {
				this.getAttachments()
					.then(() => {
						this.setAccessRights().then(() => {
							this.loading = false;
						});
					})
					.catch((e) => {
						this.loading = false;
						this.displayErrorMessage(e);
					});
			}
		});

		this.addEvents();
	},
	methods: {
		// Getters and setters
		async setDisplayedUser() {
			const response = await fileService.getFnumInfos(this.displayedFnum);

			if (response && response.fnumInfos) {
				const userStore = useUserStore();
				this.fnumInfos = response.fnumInfos;
				const foundUser =
					this.users && this.users.length
						? this.users.find((user) => user.user_id == response.fnumInfos.applicant_id)
						: false;

				if (!foundUser) {
					const resp = await userService.getUserNameById(response.fnumInfos.applicant_id);
					if (resp.status) {
						this.users.push(resp.user);
						this.displayedUser = resp.user;
						userStore.setDisplayedUser(this.displayedUser.user_id);
						userStore.setUsers([resp.user]);
					} else {
						this.displayErrorMessage(this.translate('COM_EMUNDUS_ATTACHMENTS_USER_NOT_FOUND'));
					}
				} else {
					this.displayedUser = foundUser;
					userStore.setDisplayedUser(this.displayedUser.user_id);
					userStore.setUsers([foundUser]);
				}
			} else {
				this.displayErrorMessage(this.translate('COM_EMUNDUS_ATTACHMENTS_USER_NOT_FOUND'));
			}
		},
		async getAttachments() {
			if (!this.attachmentStore.attachments[this.displayedFnum]) {
				await this.refreshAttachments();
			} else {
				this.attachments = this.attachmentStore.attachments[this.displayedFnum];
				this.categories = this.attachmentStore.categories;
			}
		},
		async refreshAttachments(addLoading = false) {
			if (addLoading === true) {
				this.loading = true;
			}

			this.resetOrder();
			this.checkedAttachments = [];
			this.$refs['searchbar'].value = '';
			const response = await attachmentService.getAttachmentsByFnum(this.displayedFnum);

			if (response.status) {
				this.attachments = response.attachments;
				this.attachmentStore.setAttachmentsOfFnum(this.displayedFnum, this.attachments);

				const categoriesResponse = await this.getAttachmentCategories();
				this.categories = categoriesResponse ? categoriesResponse : {};
			} else {
				this.displayErrorMessage(this.translate('COM_EMUNDUS_ATTACHMENTS_ERROR_GETTING_ATTACHMENTS'));
			}

			if (addLoading === true) {
				this.loading = false;
			}
		},
		updateAttachment() {
			this.resetOrder();
			this.getAttachments();
			this.selectedAttachment = {};
			this.checkedAttachments = [];
		},
		updateStatus($event, selectedAttachment) {
			if (this.canUpdate) {
				if (this.attachments.length < 1) {
					return;
				}

				this.attachments.forEach((attachment, key) => {
					if (attachment.aid == selectedAttachment.aid) {
						this.resetOrder();
						this.attachments[key].is_validated = $event.target.value;

						useUserStore();
						let formData = new FormData();
						formData.append('fnum', this.displayedFnum);
						formData.append('user', useUserStore().currentUser);
						formData.append('id', this.attachments[key].aid);
						formData.append('is_validated', this.attachments[key].is_validated);

						attachmentService
							.updateAttachment(formData)
							.then((response) => {
								if (response && response.status === false) {
									this.displayErrorMessage(response.msg);
								}
							})
							.catch((error) => {
								this.displayErrorMessage(error);
							});
					}
				});
			}
		},
		changePermission(permission, selectedAttachment) {
			if (this.canUpdate) {
				this.attachments.forEach((attachment, key) => {
					if (attachment.aid == selectedAttachment.aid) {
						this.resetOrder();
						this.attachments[key][permission] = this.attachments[key][permission] === '1' ? '0' : '1';

						let formData = new FormData();
						formData.append('fnum', this.displayedFnum);
						formData.append('user', useUserStore().currentUser);
						formData.append('id', this.attachments[key].aid);
						formData.append(permission, this.attachments[key][permission]);

						attachmentService.updateAttachment(formData).then((response) => {
							if (!response.status) {
								this.displayErrorMessage(response.msg);
							}
						});
					}
				});
			} else {
				this.displayErrorMessage(this.translate('COM_EMUNDUS_ATTACHMENTS_UNAUTHORIZED_ACTION'));
			}
		},
		synchronizeAttachments(aids) {
			let synchronized = false;

			if (this.sync && aids.length > 0) {
				syncService.synchronizeAttachments(aids).then((response) => {
					if (response && response.status === false) {
						this.displayErrorMessage(response.msg);
					} else {
						this.refreshAttachments(true);
						synchronized = true;
					}
				});
			}

			return synchronized;
		},
		async setAccessRights() {
			if (!useUserStore().rights[this.displayedFnum]) {
				const response = await userService.getAccessRights(useUserStore().currentUser, this.displayedFnum);

				if (response.status === true) {
					useUserStore().setAccessRights({
						fnum: this.displayedFnum,
						rights: response.rights,
					});
				}
			}
			this.canCreate = useUserStore().rights[this.displayedFnum]
				? useUserStore().rights[this.displayedFnum].canCreate
				: false;
			this.canExport = useUserStore().rights[this.displayedFnum]
				? useUserStore().rights[this.displayedFnum].canExport
				: false;
			this.canDelete = useUserStore().rights[this.displayedFnum]
				? useUserStore().rights[this.displayedFnum].canDelete
				: false;
			this.canUpdate = useUserStore().rights[this.displayedFnum]
				? useUserStore().rights[this.displayedFnum].canUpdate
				: false;
		},
		async exportAttachments() {
			if (this.canExport) {
				attachmentService
					.exportAttachments(this.displayedUser.user_id, this.displayedFnum, this.checkedAttachments)
					.then((response) => {
						if (response.status === true) {
							window.open(response.link, '_blank');
							this.exportLink = response.link;
						} else {
							this.displayErrorMessage(response.msg);
						}
					});
			}
		},
		confirmDeleteAttachments() {
			if (this.canDelete) {
				let html = '<p>' + this.translate('CONFIRM_DELETE_SELETED_ATTACHMENTS') + '</p><br>';

				let list = '';
				this.checkedAttachments.forEach((aid) => {
					this.attachments.forEach((attachment) => {
						if (attachment.aid == aid) {
							list += attachment.value + ', ';
						}
					});
				});

				// remove last ", "
				list = list.substring(0, list.length - 2);
				html += '<p>' + list + '</p>';

				Swal.fire({
					title: this.translate('DELETE_SELECTED_ATTACHMENTS'),
					html: html,
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: this.translate('JYES'),
					cancelButtonText: this.translate('JNO'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						cancelButton: 'em-swal-cancel-button',
						confirmButton: 'em-swal-confirm-button',
					},
				}).then((result) => {
					if (result.value) {
						this.deleteAttachments();
					}
				});
			} else {
				this.displayErrorMessage(this.translate('YOU_NOT_HAVE_PERMISSION_TO_DELETE_ATTACHMENTS'));
			}
		},
		async deleteAttachments() {
			if (this.canDelete) {
				// remove all checked attachments from attachments array
				this.attachments = this.attachments.filter((attachment) => !this.checkedAttachments.includes(attachment.aid));

				let response = null;
				if (this.sync) {
					syncService.deleteAttachments(this.checkedAttachments).then(async (sync_response) => {
						if (sync_response.status === false && sync_response.msg !== '') {
							this.displayErrorMessage(sync_response.msg);
						}

						response = await attachmentService.deleteAttachments(
							this.displayedFnum,
							this.displayedUser.user_id,
							this.checkedAttachments,
						);
					});
				} else {
					response = await attachmentService.deleteAttachments(
						this.displayedFnum,
						this.displayedUser.user_id,
						this.checkedAttachments,
					);
				}

				if (response.status === false && response.msg !== '') {
					this.displayErrorMessage(response.msg);
				}
			} else {
				this.displayErrorMessage(this.translate('YOU_NOT_HAVE_PERMISSION_TO_DELETE_ATTACHMENTS'));
			}
		},
		changeAttachment(position, reverse = false) {
			this.slideTransition = reverse ? 'slide-fade-reverse' : 'slide-fade';
			this.modalLoading = true;
			this.selectedAttachment = this.displayedAttachments[position];
			this.attachmentStore.setSelectedAttachment(this.selectedAttachment);

			setTimeout(() => {
				this.modalLoading = false;
			}, 500);
		},
		resetOrder() {
			this.sort = { last: '', order: '', orderBy: '' };
		},
		orderBy(key) {
			// if last sort is the same as the current sort, reverse the order
			if (this.sort.last === key) {
				this.sort.order = this.sort.order === 'asc' ? 'desc' : 'asc';
				this.attachments.reverse();
			} else {
				// sort in ascending order by key
				this.attachments.sort((a, b) => {
					if (a[key] < b[key]) {
						return -1;
					}
					if (a[key] > b[key]) {
						return 1;
					}
					return 0;
				});

				this.sort.order = 'asc';
			}

			this.sort.orderBy = key;
			this.sort.last = key;
		},
		updateAllCheckedAttachments(e) {
			if (e.target.checked) {
				// check all input that has class attachment-check and add them to the checkedAttachments array
				this.displayedAttachments.map((attachment) =>
					this.$refs['attachment-row-' + attachment.aid][0].checkedAttachments.push(attachment.aid),
				);
				this.checkedAttachments = this.displayedAttachments.map((attachment) => attachment.aid);
			} else {
				this.displayedAttachments.map(
					(attachment) => (this.$refs['attachment-row-' + attachment.aid][0].checkedAttachments = []),
				);
				this.checkedAttachments = [];
			}

			this.attachmentStore.setCheckedAttachments(this.checkedAttachments);
		},
		filterCheckedAttachments() {
			this.checkedAttachments = this.checkedAttachments.filter((aid) => {
				return this.displayedAttachments.some((attachment) => {
					return attachment.aid == aid;
				});
			});
		},

		onSearch() {
			this.filterCheckedAttachments();
		},

		onSearchKeyup(e) {
			e.stopPropagation();
			e.preventDefault();
			this.onSearch();
		},
		openModal(attachment) {
			if (this.displayedUser.user_id && this.displayedFnum) {
				this.openedModal = true;
				this.selectedAttachment = attachment;
				this.attachmentStore.setSelectedAttachment(attachment);
			}
		},
		closeModal() {
			this.openedModal = false;
			this.selectedAttachment = {};
			this.attachmentStore.setSelectedAttachment({});
		},
		displayErrorMessage(msg) {
			Swal.fire({
				title: this.translate('ERROR'),
				text: msg,
				icon: 'error',
				confirmButtonColor: '#3085d6',
				confirmButtonText: this.translate('COM_EMUNDUS_ATTACHMENTS_CLOSE'),
			});
		},

		// Transition hooks
		beforeLeaveSlide(el) {
			if (this.slideTransition === 'slide-fade') {
				el.style.transform = 'translateX(-100%)';
			}

			el.setAttribute(
				'class',
				'modal-body ' + this.slideTransition + '-leave-active ' + this.slideTransition + '-leave-to',
			);
		},
		toggleOnlyPreview(editDisplayed) {
			this.onlyPreview = !editDisplayed;
		},
		addEvents() {
			window.addEventListener(
				'message',
				function (e) {
					if (e.data === 'addFileToFnum') {
						this.refreshAttachments(true);
					}
				}.bind(this),
			);
		},
		addAttachment() {
			Swal.fire({
				html:
					'<iframe style="width:' +
					window.innerWidth * 0.8 +
					'px; height:' +
					window.innerHeight * 0.8 +
					'px;" src="/component/fabrik/form/67/?jos_emundus_uploads___user_id[value]=' +
					this.fnumInfos.applicant_id +
					'&jos_emundus_uploads___fnum[value]=' +
					this.displayedFnum +
					'&student_id=' +
					this.fnumInfos.applicant_id +
					'&jos_emundus_uploads___campaign_id[value]=' +
					this.fnumInfos.campaign_id +
					'&tmpl=component&iframe=1&action_id=4"></iframe>',
				showCancelButton: true,
				showCloseButton: true,
				reverseButtons: true,

				cancelButtonText: Joomla.JText._('COM_EMUNDUS_ONBOARD_CANCEL'),
				customClass: {
					container: 'em-modal-actions add-attachment-modal',
					popup: 'em-w-auto',
					title: 'em-swal-title',
					cancelButton: 'em-swal-cancel-button',
					confirmButton: 'em-swal-confirm-button btn btn-success',
					actions: 'em-actions-none',
				},
			});
		},
	},
	computed: {
		fnumPosition() {
			return this.fnums.indexOf(this.displayedFnum);
		},
		selectedAttachmentPosition() {
			return this.displayedAttachments.indexOf(this.selectedAttachment);
		},
		attachmentPath() {
			return (
				'/index.php?option=com_emundus&task=getfile&u=' +
				this.attachmentStore.attachmentPath +
				this.displayedUser.user_id +
				'/' +
				this.selectedAttachment.filename
			);
		},
		displayedAttachmentCategories() {
			let displayedCategories = {};
			if (Object.entries(this.categories).length > 0) {
				for (let category in this.categories) {
					for (let attachment in this.attachments) {
						if (this.attachments[attachment].category == category) {
							displayedCategories[category] = this.categories[category];
						}
					}
				}
			}

			return displayedCategories;
		},
		displayedAttachments() {
			let displayedAttachments = [];

			if (typeof this.attachments !== 'undefined' && this.attachments !== null) {
				const currentSearch = this.search.toLowerCase();
				displayedAttachments = this.attachments.filter((attachment) => {
					if (attachment.upload_description === null) {
						attachment.upload_description = '';
					}

					return (
						(attachment.upload_description.toLowerCase().includes(currentSearch) ||
							attachment.value.toLowerCase().includes(currentSearch)) &&
						(this.category === 'all' || attachment.category === this.category)
					);
				});
			}

			return displayedAttachments;
		},
	},
	watch: {
		selectedAttachment: function () {
			this.syncSelectedPreview = null;
			syncService.getAttachmentSyncNodeId(this.selectedAttachment.aid).then((response) => {
				this.syncSelectedPreview = response;
			});
		},
		checkedAttachments: function () {
			this.attachmentStore.setCheckedAttachments(this.checkedAttachments);
		},
		categories: function () {
			const localCategory = localStorage.getItem('vue-attachment-category');
			if (localCategory && this.displayedAttachmentCategories[localCategory]) {
				this.category = localCategory;
			}
		},
		category: function () {
			if (this.category !== 'all') {
				localStorage.setItem('vue-attachment-category', this.category);
			} else {
				localStorage.removeItem('vue-attachment-category');
			}
			this.filterCheckedAttachments();
		},
	},
};
</script>

<style lang="scss">
#em-attachments {
	font-size: 14px;

	#em-attachment-preview {
		width: 75%;
	}

	#attachment-edit {
		width: 25%;
	}

	.v--modal-box.v--modal {
		height: 100vh !important;
		width: 100vw !important;
		top: 0 !important;
		left: 0 !important;
	}

	.head {
		align-items: center;
		margin-top: 1px;
		padding: 10px;
		min-height: 50px;
		background-color: var(--night-blue);

		.displayed-user {
			align-items: baseline;

			p {
				color: var(--text-light-color);
			}

			.name {
				font-size: 18px;
				font-weight: 800;
				line-height: 23px;
			}

			.email {
				margin-left: 8px !important;
				font-size: 12px;
			}
		}

		.prev-next-files {
			align-items: center;
			margin-right: 12px;
			width: 75px;

			> div {
				pointer-events: none;
				color: transparent;
				transition: all 0.3s;

				span {
					height: 30px;
					width: 30px;
					display: flex;
					justify-content: center;
					align-items: center;
					margin: 0;
					opacity: 0;
				}

				&.active {
					pointer-events: auto;
					cursor: pointer;
					span {
						color: var(--text-light-color);
						opacity: 1;
					}
				}

				&:hover {
					border-radius: 4px;
					background-color: transparent;

					span {
						color: var(--primary-color);
					}
				}
			}
		}
	}

	#filters {
		margin-bottom: 20px;

		.searchbar-wrapper {
			position: relative;

			.material-symbols-outlined.search {
				position: absolute;
				left: 8px;
			}

			.material-symbols-outlined.clear {
				position: absolute;
				right: 8px;
			}

			#searchbar {
				padding-left: 40px;
				height: 40px;
				border: 1px solid var(--border-color);
			}
		}

		.actions {
			align-items: center;
			justify-content: flex-end;

			select {
				height: 42px;
				border: 1px solid var(--border-color);
			}

			> div,
			> select {
				margin-right: 8px;

				&.disabled {
					color: var(--disabled-color);
					pointer-events: none;

					.material-icons,
					.material-symbols-outlined {
						color: var(--disabled-color);
					}
				}
			}
		}

		.refresh {
			transition: transform 0.6s;

			&:hover {
				transform: rotate(360deg);
				color: var(--primary-color);
			}
		}

		input {
			align-self: flex-start;
			width: 221px;
		}
	}

	.wrapper {
		margin: 20px;
		width: calc(100% - 40px);

		&.loading {
			min-height: 50vh;
		}

		.material-symbols-outlined.delete {
			transition: all 0.3s;
			width: 30px;
			color: var(--red-500);

			&.disabled {
				color: var(--disabled-color);
				pointer-events: none;
			}

			&:hover {
				cursor: pointer;
				color: var(--red-500);
			}
		}
	}

	.table-wrapper {
		overflow-x: scroll;
	}

	table {
		&.loading {
			visibility: hidden;
		}

		tr {
			th:first-of-type {
				width: 39px;

				input {
					margin-right: 0;
				}
			}
		}

		tr,
		th {
			height: 49px;
			background: transparent;
		}

		td,
		th {
			width: fit-content;
		}

		th.desc,
		td.desc {
			max-width: 250px;
			width: initial;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		th.status,
		td.status {
			min-width: 100px;
			white-space: nowrap;
		}

		thead {
			tr {
				th {
					border-top: 1px solid #e0e0e0;
					border-bottom: 1px solid #e0e0e0;
					white-space: nowrap;

					.material-icons,
					.material-symbols-outlined {
						transform: translateY(3px);
					}
				}
			}
		}

		.attachment-check {
			width: 15px;
			height: 15px;
			border-radius: 0;
		}
	}

	.modal-head {
		width: 100%;
		align-items: center;
		padding: 16px;
		border-bottom: 1px solid var(--border-color);

		#actions-left {
			span:first-child {
				margin: 0 8px 0 20px;
				cursor: pointer;
			}
		}

		#actions-right {
			.download {
				height: 32px;
				color: black;

				.material-icons,
				.material-symbols-outlined {
					font-size: 18px;
				}

				span:last-child {
					margin-top: -1px;
					margin-left: 4px;
				}
			}

			.prev-next-attachments {
				.lvl {
					padding: 6px 8px 7px 8px;
					background-color: var(--grey-bg-color);
				}

				.prev {
					border-radius: 4px 0 0 4px;
				}

				.next {
					border-radius: 0 4px 4px 0;
				}

				.prev,
				.next {
					justify-content: center;
					pointer-events: none;
					height: 32px;
					width: 32px;
					background-color: var(--grey-bg-color);

					span {
						color: initial;
					}

					&.active {
						pointer-events: auto;
						cursor: pointer;
					}
				}
			}
		}
	}

	.slide-fade-enter-active {
		transition: all 0.5s ease;
	}

	.slide-fade-leave-active {
		transition: all 0.5s cubic-bezier(1, 0.5, 0.8, 1);
	}

	.slide-fade-enter {
		transform: translateX(100%);
	}

	.slide-fade-leave-to {
		transform: translateX(-100%);
		opacity: 0;
	}

	.slide-fade-reverse-enter-active {
		transition: all 0.5s ease;
	}

	.slide-fade-reverse-leave-active {
		transition: all 0.5s cubic-bezier(1, 0.8, 0.5, 1);
	}

	.slide-fade-reverse-enter {
		transform: translateX(-100%);
	}

	.slide-fade-reverse-leave-to {
		transform: translateX(100%);
		opacity: 0;
	}

	.modal-body {
		height: calc(100vh - 65px);
		width: 100vw;
		display: flex;
		padding: 0;
		max-height: unset !important;

		&.only-preview {
			#em-attachment-preview {
				width: 100% !important;
			}

			#attachment-edit {
				width: 0 !important;
				padding: 0;

				.wrapper,
				.actions {
					display: none;
				}
			}
		}
	}
}

#add-document-section {
	justify-content: center;
}
</style>
