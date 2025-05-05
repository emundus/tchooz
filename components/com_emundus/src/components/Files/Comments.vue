<template>
	<div
		id="comments"
		class="tw-flex tw-w-full tw-flex-col tw-bg-[#f8f8f8] tw-p-4"
		:class="{ 'tw-border-l-4 tw-border-profile-full': border }"
	>
		<div v-if="comments.length > 0" id="filter-comments" class="tw-flex tw-flex-row tw-flex-wrap tw-gap-2">
			<input
				type="text"
				class="em-input tw-mr-2"
				:placeholder="translate('COM_EMUNDUS_COMMENTS_SEARCH')"
				v-model="search"
				@keyup="onSearchChange"
			/>
			<select v-model="filterOpenedState" class="tw-mr-2 tw-rounded-applicant">
				<option value="all">
					{{ translate('COM_EMUNDUS_COMMENTS_ALL_THREAD') }}
				</option>
				<option value="1">
					{{ translate('COM_EMUNDUS_COMMENTS_OPENED_THREAD') }}
				</option>
				<option value="0">
					{{ translate('COM_EMUNDUS_COMMENTS_CLOSED_THREAD') }}
				</option>
			</select>
			<select v-model="filterVisibleToApplicant" v-if="!isApplicant" class="tw-rounded-applicant">
				<option value="all">
					{{ translate('COM_EMUNDUS_COMMENTS_VISIBLE_ALL_OPT') }}
				</option>
				<option value="0">
					{{ translate('COM_EMUNDUS_COMMENTS_VISIBLE_PARTNERS') }}
				</option>
				<option value="1">
					{{ translate('COM_EMUNDUS_COMMENTS_VISIBLE_ALL') }}
				</option>
			</select>
		</div>

		<div v-if="parentComments.length > 0" id="comments-list-container" class="tw-p-1">
			<div
				:id="'file-comment-' + comment.id"
				v-for="comment in parentComments"
				:key="comment.id"
				class="tw-group tw-my-4 tw-rounded-lg tw-border tw-bg-white tw-px-4 tw-py-2 tw-shadow"
				:class="{
					'tw-border-transparent': comment.id != openedCommentId,
					'tw-focus tw-border-profile-full': comment.id == openedCommentId,
					'tw-lightgray-bg tw-border-left-600': comment.opened == 0,
					'tw-white-bg': comment.opened == 1,
				}"
			>
				<div class="file-comment-header tw-mb-3 tw-flex tw-flex-col">
					<div class="tw-flex tw-flex-row tw-items-center tw-justify-between">
						<div
							class="file-comment-header-left tw-flex tw-w-full tw-cursor-pointer tw-flex-row tw-items-center tw-justify-between"
							@click="replyToComment(comment.id)"
						>
							<div class="tw-flex tw-flex-row tw-items-center">
								<div
									class="profile-picture tw-mr-2 tw-flex tw-h-8 tw-w-8 tw-flex-row tw-items-center tw-justify-center tw-rounded-full tw-border-2"
									:class="{ 'tw-bg-neutral-300': !comment.profile_picture }"
								>
									<div
										v-if="comment.profile_picture"
										class="image tw-h-full tw-w-full tw-rounded-full"
										:style="
											'background-image: url(' +
											comment.profile_picture +
											');background-size: cover;background-position: center;'
										"
									></div>
									<span v-else class="tw-text-sm"
										>{{ comment.firstname.charAt(0).toUpperCase() }}{{ comment.lastname.charAt(0).toUpperCase() }}</span
									>
								</div>
								<div class="tw-mr-3 tw-flex tw-flex-col">
									<span class="tw-text-xs tw-text-neutral-500">{{
										comment.updated ? comment.updated : comment.date
									}}</span>
									<span class="tw-text-xs">{{ comment.username }}</span>
								</div>
							</div>
							<div>
								<span
									v-if="childrenComments[comment.id].length > 0"
									class="label tw-bg-profile-medium !tw-text-neutral-900"
								>
									{{ childrenComments[comment.id].length }}
									{{
										childrenComments[comment.id].length > 1
											? translate('COM_EMUNDUS_COMMENTS_ANSWERS')
											: translate('COM_EMUNDUS_COMMENTS_ANSWER')
									}}
								</span>
							</div>
						</div>
					</div>
				</div>

				<div class="tw-flex tw-flex-row tw-items-start tw-justify-between">
					<div class="tw-w-full">
						<div v-if="editable == comment.id" class="tw-w-full">
							<textarea
								:id="'editable-comment-' + comment.id"
								class="comment-body"
								v-model="comment.comment_body"
								@keyup.enter="updateComment(comment.id)"
							></textarea>
							<div class="tw-mt-2 tw-flex tw-flex-row tw-justify-end">
								<button id="add-comment-btn" class="tw-btn-primary tw-w-fit" @click="updateComment(comment.id)">
									<span>{{ translate('COM_EMUNDUS_COMMENTS_UPDATE_COMMENT') }}</span>
									<span class="material-symbols-outlined tw-ml-1 tw-text-neutral-300">send</span>
								</button>
								<button id="abort-update" class="tw-btn-secondary tw-ml-2 tw-w-fit" @click="abortUpdateComment">
									<span>{{ translate('COM_EMUNDUS_COMMENTS_CANCEL') }}</span>
								</button>
							</div>
						</div>
						<p class="comment-body" v-else>{{ comment.comment_body }}</p>
					</div>
					<div
						v-if="editable != comment.id"
						class="file-comment-header-right tw-flex tw-flex-row tw-opacity-0 tw-duration-300 tw-ease-in-out group-hover:tw-opacity-100"
					>
						<span class="material-symbols-outlined tw-cursor-pointer" @click="replyToComment(comment.id)">reply</span>
						<span
							v-if="access.d || comment.user_id == user"
							class="material-symbols-outlined em-red-500-color tw-cursor-pointer"
							@click="deleteComment(comment.id)"
							>delete</span
						>
						<span
							v-if="access.u || (access.c && comment.user_id == user)"
							class="material-symbols-outlined tw-cursor-pointer"
							@click="makeCommentEditable(comment.id)"
							>edit</span
						>
					</div>
				</div>
				<i v-if="comment.updated_by > 0" class="em-gray-color tw-mt-3 tw-text-xs">{{
					translate('COM_EMUNDUS_COMMENTS_EDITED')
				}}</i>

				<div
					class="comment-children"
					:class="{
						opened: openedCommentId == comment.id,
						hidden: openedCommentId !== comment.id,
					}"
				>
					<hr />
					<div :id="'file-comment-' + child.id" v-for="child in childrenComments[comment.id]" :key="child.id" dir="ltr">
						<div class="child-comment tw-my-3 tw-flex tw-flex-col tw-border-s-4 tw-px-3">
							<div class="file-comment-header tw-mb-2 tw-flex tw-flex-row tw-justify-between">
								<div class="file-comment-header-left tw-flex tw-flex-col">
									<div class="tw-flex tw-flex-row tw-items-center">
										<div
											class="profile-picture tw-mr-2 tw-flex tw-h-8 tw-w-8 tw-flex-row tw-items-center tw-justify-center tw-rounded-full tw-border-2"
										>
											<div
												v-if="comment.profile_picture"
												class="image tw-h-full tw-w-full tw-rounded-full"
												:style="
													'background-image: url(' +
													comment.profile_picture +
													'); background-size: cover;background-position: center;'
												"
											></div>
											<span v-else>{{ comment.firstname.charAt(0) }}{{ comment.lastname.charAt(0) }}</span>
										</div>
										<div class="tw-mr-3 tw-flex tw-flex-col">
											<span class="tw-text-xs tw-text-neutral-500">{{
												child.updated ? child.updated : child.date
											}}</span>
											<span class="tw-text-xs">{{ child.username }}</span>
										</div>
									</div>
								</div>
							</div>

							<div class="tw-flex tw-flex-row tw-items-start tw-justify-between">
								<div class="tw-w-full">
									<div v-if="editable == child.id" class="tw-w-full">
										<textarea
											:id="'editable-comment-' + child.id"
											class="comment-body"
											v-model="child.comment_body"
											@keyup.enter="updateComment(child.id)"
										></textarea>
										<div class="tw-mt-2 tw-flex tw-flex-row tw-justify-end">
											<button id="add-comment-btn" class="tw-btn-primary tw-w-fit" @click="updateComment(child.id)">
												<span>{{ translate('COM_EMUNDUS_COMMENTS_UPDATE_COMMENT') }}</span>
												<span class="material-symbols-outlined tw-ml-1 tw-text-neutral-300">send</span>
											</button>
											<button id="abort-update" class="tw-btn-secondary tw-ml-2 tw-w-fit" @click="abortUpdateComment">
												<span>{{ translate('COM_EMUNDUS_COMMENTS_CANCEL') }}</span>
											</button>
										</div>
									</div>
									<p class="comment-body" v-else>{{ child.comment_body }}</p>
								</div>
								<div v-if="editable != child.id" class="tw-flex tw-flex-row tw-items-center">
									<span
										v-if="access.d || child.user_id == user"
										class="material-symbols-outlined em-red-500-color tw-cursor-pointer"
										@click="deleteComment(child.id)"
										>delete</span
									>
									<span
										v-if="access.u || (access.c && child.user_id == user)"
										class="material-symbols-outlined tw-cursor-pointer"
										@click="makeCommentEditable(child.id)"
										>edit</span
									>
								</div>
							</div>
							<i v-if="child.updated_by > 0" class="em-gray-color tw-mt-3 tw-text-xs">{{
								translate('COM_EMUNDUS_COMMENTS_EDITED')
							}}</i>
						</div>
					</div>
					<div class="add-child-comment">
						<textarea
							class="tw-mb-2 tw-p-2"
							@keyup.enter="addComment(comment.id)"
							v-model="newChildCommentText"
							:placeholder="translate('COM_EMUNDUS_COMMENTS_ADD_COMMENT_PLACEHOLDER')"
						></textarea>
						<div class="tw-mt-2 tw-flex tw-w-full tw-flex-row tw-items-center tw-justify-between">
							<button
								id="add-comment-btn"
								class="tw-btn-primary tw-w-fit tw-bg-profile-full tw-text-neutral-300"
								:class="{
									'tw-cursor-not-allowed tw-opacity-50': newChildCommentText.length === 0,
								}"
								:disabled="newChildCommentText.length === 0"
								@click="addComment(comment.id)"
							>
								<span>{{ translate('COM_EMUNDUS_COMMENTS_ADD_COMMENT') }}</span>
								<span class="material-symbols-outlined tw-ml-1 tw-text-neutral-300">send</span>
							</button>

							<button
								class="tw-btn-secondary tw-w-fit"
								v-if="comment.opened == 1"
								@click="updateCommentOpenedState(comment.id, 0)"
							>
								<span
									:title="translate('COM_EMUNDUS_COMMENTS_CLOSE_COMMENT_THREAD')"
									class="material-symbols-outlined tw-text-neutral-300"
									>check_circle</span
								>
							</button>
							<button class="tw-btn-secondary tw-w-fit" v-else @click="updateCommentOpenedState(comment.id, 1)">
								<span
									:title="translate('COM_EMUNDUS_COMMENTS_REOPEN_COMMENT_THREAD')"
									class="material-symbols-outlined tw-text-neutral-300"
									>unpublished</span
								>
							</button>
						</div>
					</div>
				</div>

				<p
					v-if="comment.target_id > 0"
					class="comment-target-label em-gray-color tw-mt-4 tw-cursor-pointer tw-text-sm"
					@click="goToCommentTarget(comment)"
				>
					{{ getCommentTargetLabel(comment.target_id, comment.target_type) }}
				</p>
			</div>
		</div>
		<p v-else id="empty-comments" class="tw-my-4 tw-text-center">
			{{ translate('COM_EMUNDUS_COMMENTS_NO_COMMENTS') }}
		</p>

		<hr />

		<div id="add-comment-container">
			<label for="new-comment" class="tw-font-medium">{{ translate('COM_EMUNDUS_COMMENTS_ADD_GLOBAL_COMMENT') }}</label>
			<textarea
				id="new-comment"
				@keyup.enter="addComment"
				v-model="newCommentText"
				class="tw-mt-1 tw-p-2"
				:placeholder="translate('COM_EMUNDUS_COMMENTS_ADD_COMMENT_PLACEHOLDER')"
			></textarea>
			<div v-if="!isApplicant && applicantsAllowedToComment" class="tw-mt-3 tw-flex tw-flex-row tw-items-center">
				<div class="tw-mr-2 tw-flex tw-flex-row tw-items-center">
					<input
						type="radio"
						name="visible_to_applicant"
						v-model="visibleToApplicant"
						:value="false"
						id="visible-to-coords"
					/>
					<label for="visible-to-coords" class="tw-m-0">{{ translate('COM_EMUNDUS_COMMENTS_VISIBLE_PARTNERS') }}</label>
				</div>
				<div class="tw-flex tw-flex-row tw-items-center">
					<input
						type="radio"
						name="visible_to_applicant"
						v-model="visibleToApplicant"
						:value="true"
						id="visible-to-applicant"
					/>
					<label for="visible-to-applicant" class="tw-m-0">{{ translate('COM_EMUNDUS_COMMENTS_VISIBLE_ALL') }}</label>
				</div>
			</div>
			<div class="tw-mt-4 tw-flex tw-flex-row tw-justify-end">
				<button
					id="add-comment-btn"
					class="tw-btn-primary tw-w-fit tw-bg-profile-full tw-text-neutral-300"
					:class="{
						'!tw-cursor-not-allowed tw-opacity-50': newCommentText.length === 0,
					}"
					:disabled="newCommentText.length === 0"
					@click="addComment(0)"
				>
					<span>{{ translate('COM_EMUNDUS_COMMENTS_ADD_COMMENT') }}</span>
					<span class="material-symbols-outlined tw-ml-1 tw-text-neutral-300">send</span>
				</button>
			</div>
		</div>
		<div class="em-page-loader" v-if="loading"></div>

		<div v-show="openedModal">
			<modal
				:name="'add-comment-modal'"
				height="30em"
				width="30em"
				transition="fade"
				:delay="100"
				:clickToClose="false"
				ref="modal"
			>
				<div class="tw-flex tw-h-full tw-w-full tw-flex-col tw-justify-between tw-p-4">
					<div>
						<h2 class="tw-mb-3">
							{{ translate('COM_EMUNDUS_COMMENTS_ADD_COMMENT_ON') }}
							{{ targetLabel }}
						</h2>
						<textarea
							v-model="newCommentText"
							class="tw-h-full tw-p-2"
							:placeholder="translate('COM_EMUNDUS_COMMENTS_ADD_COMMENT_PLACEHOLDER')"
						></textarea>
						<div v-if="!isApplicant && applicantsAllowedToComment" class="tw-flex tw-flex-row tw-items-center">
							<div class="tw-flex tw-flex-row tw-items-center">
								<input
									type="radio"
									name="visible_to_applicant"
									v-model="visibleToApplicant"
									:value="false"
									id="visible-to-coords"
								/>
								<label for="visible-to-coords" class="tw-m-0">{{
									translate('COM_EMUNDUS_COMMENTS_VISIBLE_PARTNERS')
								}}</label>
							</div>
							<div class="tw-ml-2 tw-flex tw-flex-row tw-items-center">
								<input
									type="radio"
									name="visible_to_applicant"
									v-model="visibleToApplicant"
									:value="true"
									id="visible-to-applicant"
								/>
								<label for="visible-to-applicant" class="tw-m-0">{{
									translate('COM_EMUNDUS_COMMENTS_VISIBLE_ALL')
								}}</label>
							</div>
						</div>
					</div>

					<div class="tw-flex tw-flex-row tw-justify-between">
						<button @click="hideModal()" class="tw-btn-cancel">
							{{ translate('COM_EMUNDUS_COMMENTS_CANCEL') }}
						</button>
						<button
							id="add-comment-btn"
							class="tw-btn-primary tw-w-fit tw-bg-profile-full tw-text-neutral-300"
							:class="{
								'tw-cursor-not-allowed tw-opacity-50': newCommentText.length === 0,
							}"
							:disabled="newCommentText.length === 0"
							@click="addComment(0)"
						>
							<span>{{ translate('COM_EMUNDUS_COMMENTS_ADD_COMMENT') }}</span>
							<span class="material-symbols-outlined tw-ml-1 tw-text-neutral-300">send</span>
						</button>
					</div>
				</div>
			</modal>
		</div>
	</div>
</template>

<script>
import commentsService from '@/services/comments.js';
import mixins from '@/mixins/mixin.js';
import alerts from '@/mixins/alerts.js';
import Modal from '@/components/Modal.vue';
import fileService from '@/services/file.js';

export default {
	name: 'Comments',
	components: { Modal },
	props: {
		user: {
			type: String,
			required: true,
		},
		fnum: {
			type: String,
			default: '', // soon deprecated
		},
		defaultCcid: {
			type: Number,
			default: 0,
		},
		access: {
			type: Object,
			default: () => ({
				c: false,
				r: true,
				u: false,
				d: false,
			}),
		},
		isApplicant: {
			type: Boolean,
			default: false,
		},
		currentForm: {
			type: Number,
			default: 0,
		},
		applicantsAllowedToComment: {
			type: Boolean,
			default: false,
		},
		border: {
			type: Boolean,
			default: true,
		},
	},
	mixins: [mixins, alerts],
	data: () => ({
		ccid: 0,
		comments: [],
		newCommentText: '',
		newChildCommentText: '',
		target: {
			type: 'elements',
			id: 0,
		},
		visibleToApplicant: false,
		openedCommentId: 0,
		loading: false,
		targetableElements: {
			elements: [],
			groups: [],
			forms: [],
		},
		focus: null,
		editable: null,
		tmpComment: null,
		search: '',
		filterOpenedState: '1',
		filterVisibleToApplicant: 'all',

		openedModal: false,
	}),
	created() {
		if (this.defaultCcid == 0) {
			fileService.getFileIdFromFnum(this.fnum).then((response) => {
				if (response.status) {
					this.ccid = response.data;

					this.init();
				}
			});
		} else {
			this.ccid = this.defaultCcid;
			this.init();
		}
	},
	beforeDestroy() {
		document.removeEventListener('openModalAddComment');
		document.removeEventListener('focusOnCommentElement');
	},
	methods: {
		init() {
			this.getTargetableElements().then(() => {
				this.getComments();
			});
			this.addListeners();
		},
		addListeners() {
			document.addEventListener('openModalAddComment', (event) => {
				this.target.id = event.detail.targetId;
				this.target.type = event.detail.targetType;
				this.showModal();
			});

			document.addEventListener('focusOnCommentElement', (event) => {
				if (event.detail.targetId !== null && event.detail.targetId > 0) {
					const foundComment = this.parentComments.find((comment) => {
						return comment.target_id == event.detail.targetId;
					});

					if (foundComment) {
						this.openedCommentId = foundComment.id;
						const commentElement = document.getElementById(`file-comment-${foundComment.id}`);
						if (commentElement) {
							commentElement.scrollIntoView({ behavior: 'smooth' });
						}
					}
				} else {
					this.openedCommentId = 0;
				}
			});
		},
		dispatchCommentsLoaded() {
			const event = new CustomEvent('commentsLoaded', {
				detail: {
					comments: this.parentComments,
				},
			});
			document.dispatchEvent(event);
		},
		dispatchThreadsNumber() {
			// a thread is a parent comment
			const parentComments = this.comments.filter((comment) => comment.parent_id == 0 && comment.opened == 1);

			const event = new CustomEvent('commentsThreadsNumberUdated', {
				detail: {
					number: parentComments.length,
				},
			});

			document.dispatchEvent(event);
		},
		showModal() {
			this.openedModal = true;
		},
		hideModal() {
			this.openedModal = false;
		},
		getComments() {
			this.loading = true;
			commentsService
				.getComments(this.ccid)
				.then((response) => {
					if (response.status) {
						this.comments = response.data;
					}
				})
				.catch((error) => {
					this.handleError(error);
				})
				.finally(() => {
					this.loading = false;
					this.dispatchCommentsLoaded();
				});
		},
		async getTargetableElements() {
			return await commentsService
				.getTargetableElements(this.ccid)
				.then((response) => {
					if (response.status) {
						this.targetableElements = response.data;
					}
				})
				.catch((error) => {
					this.handleError(error);
				});
		},
		getCommentTargetLabel(target_id, target_type = 'elements') {
			let label = '';

			// make sure targetableElements[target_type] entry exists
			if (!this.targetableElements[target_type]) {
				target_type = 'elements';
			}

			const target = this.targetableElements[target_type].find((element) => element.id == target_id);
			if (target) {
				if (target_type === 'elements') {
					if (target.element_form_label.length > 0) {
						label += `${target.element_form_label} > `;
					}

					if (target.element_group_label.length > 0) {
						label += `${target.element_group_label} > `;
					}
				}

				if (target_type === 'groups') {
					// find label of the form
					const form = this.targetableElements.forms.find((form) => form.id == target.form_id);
					if (form) {
						label += `${form.label} > `;
					}
				}

				label += target.label;
			}

			return label;
		},
		addComment(parent_id = 0) {
			this.loading = true;

			if (this.access.c || this.isApplicant) {
				if (this.isApplicant) {
					this.visibleToApplicant = true;
				}

				if (!this.applicantsAllowedToComment) {
					this.visibleToApplicant = false;
				}

				let commentContent = this.newCommentText;
				if (parent_id !== 0) {
					commentContent = this.newChildCommentText;
				}

				commentsService
					.addComment(this.ccid, commentContent, this.target, this.visibleToApplicant, parent_id)
					.then((response) => {
						if (response.status) {
							this.comments.push(response.data);
							this.resetAddComment();
							this.getComments();

							this.dispatchThreadsNumber();
						}
					})
					.catch((error) => {
						this.handleError(error);
					})
					.finally(() => {
						this.loading = false;
					});
			} else {
				this.loading = false;
			}
		},
		resetAddComment() {
			this.newCommentText = '';
			this.newChildCommentText = '';
			this.visibleToApplicant = false;
			this.target.id = 0;
			this.target.type = 'element';
			this.hideModal();

			if (this.openedCommentId > 0) {
				const openedComment = this.comments.find((comment) => comment.id == this.openedCommentId);
				this.visibleToApplicant = openedComment.visible_to_applicant == 1;
			}
		},
		replyToComment(commentId) {
			if (commentId > 0) {
				this.resetAddComment();
				this.openedCommentId = this.openedCommentId == commentId ? 0 : commentId;

				const openedComment = this.comments.find((comment) => comment.id == commentId);
				this.visibleToApplicant = openedComment.visible_to_applicant == 1;

				setTimeout(() => {
					if (document.querySelector('.comment-children.opened')) {
						document.querySelector('.comment-children.opened .add-child-comment textarea').focus();
					}
				}, 200);
			}
		},
		goToCommentTarget(comment) {
			if (comment.id) {
				// find the target element
				const target = this.targetableElements[comment.target_type].find((element) => element.id == comment.target_id);

				if (target) {
					let form_id = 0;
					switch (comment.target_type) {
						case 'elements':
							form_id = target.element_form_id;
							break;
						case 'groups':
							form_id = target.form_id;
							break;
						case 'forms':
							form_id = target.id;
							break;
					}

					if (form_id > 0 && this.ccid) {
						if (this.isApplicant) {
							window.location.assign('#' + comment.target_type + '-' + comment.target_id);
						} else {
							commentsService.getMenuItemForFormId(this.ccid, form_id).then((response) => {
								if (response.status) {
									window.open(
										'/' + response.data + '?fnum=' + this.fnum + '#' + comment.target_type + '-' + comment.target_id,
										'_blank',
									);
								}
							});
						}
					}
				}
			}
		},
		deleteComment(commentId) {
			const comment = this.comments.find((comment) => comment.id === commentId);
			if (commentId > 0 && (this.access.d || comment.user_id == this.user)) {
				this.alertConfirm(
					'COM_EMUNDUS_COMMENTS_CONFIRM_DELETE',
					'"' + comment.comment_body + '"',
					false,
					'COM_EMUNDUS_ACTIONS_DELETE',
				).then((response) => {
					if (response.value) {
						this.comments = this.comments.filter((comment) => comment.id !== commentId);

						commentsService
							.deleteComment(commentId)
							.then((response) => {
								if (!response.status) {
									// TODO: handle error
									this.alertError('COM_EMUNDUS_COMMENTS_DELETE_ERROR');
									this.getComments();
								}

								this.dispatchThreadsNumber();
							})
							.catch((error) => {
								this.handleError(error);
							});
					}
				});
			}
		},
		makeCommentEditable(commentId) {
			if (commentId > 0) {
				const comment = this.comments.find((comment) => comment.id == commentId);
				if (comment && comment.user_id == this.user) {
					this.editable = commentId;
					this.tmpComment = comment.comment_body;

					this.$nextTick(() => {
						const textarea = document.getElementById(`editable-comment-${commentId}`);
						if (textarea) {
							textarea.focus();
						}
					});
				}
			}
		},
		abortUpdateComment() {
			this.comments.find((comment) => comment.id == this.editable).comment_body = this.tmpComment;
			this.editable = null;
			this.tmpComment = null;
		},
		updateComment(commentId) {
			this.loading = true;

			const commentToUpdate = this.comments.find((comment) => comment.id == commentId);
			if (this.access.u || commentToUpdate.user_id == this.user) {
				const commentContent = commentToUpdate.comment_body;
				commentsService
					.updateComment(commentId, commentContent)
					.then((response) => {
						// nothing to do
					})
					.catch((error) => {
						this.handleError(error);
					})
					.finally(() => {
						this.loading = false;
						this.editable = null;
						this.tmpComment = null;
					});
			} else {
				this.abortUpdateComment();
				this.loading = false;
			}
		},
		updateCommentOpenedState(commentId, state) {
			this.loading = true;

			this.comments.find((comment) => comment.id == commentId).opened = state;
			commentsService
				.updateCommentOpenedState(commentId, state)
				.then((response) => {
					if (!response.status) {
						// todo: display error message
					}
				})
				.catch((error) => {
					this.handleError(error);
				})
				.finally(() => {
					this.loading = false;
				});
		},
		onSearchChange() {
			this.highlight(this.search, ['.comment-body', '.comment-target-label']);
		},
		handleError(error) {
			this.alertError(error);
		},
	},
	computed: {
		displayedComments() {
			let displayedComments = this.comments;
			if (this.currentForm > 0) {
				displayedComments = displayedComments.filter((comment) => {
					if (comment.target_id == 0) {
						return true;
					} else if (comment.target_type == 'elements') {
						return this.targetableElements.elements.find(
							(element) => element.id == comment.target_id && element.element_form_id == this.currentForm,
						);
					} else if (comment.target_type == 'groups') {
						return (
							this.targetableElements.groups.find((group) => group.id == comment.target_id).form_id == this.currentForm
						);
					} else if (comment.target_type == 'forms') {
						return comment.target_id == this.currentForm;
					}

					return false;
				});
			}

			displayedComments =
				this.filterOpenedState !== 'all'
					? displayedComments.filter((comment) => comment.opened == this.filterOpenedState)
					: displayedComments;
			displayedComments =
				this.filterVisibleToApplicant !== 'all'
					? displayedComments.filter((comment) => comment.visible_to_applicant == this.filterVisibleToApplicant)
					: displayedComments;

			return this.isApplicant
				? displayedComments.filter((comment) => comment.visible_to_applicant == 1)
				: displayedComments;
		},
		parentComments() {
			let parentComments = this.displayedComments.filter((comment) => parseInt(comment.parent_id) === 0);

			if (this.search.length > 0) {
				parentComments = parentComments.filter((comment) => {
					return (
						comment.comment_body.toLowerCase().includes(this.search.toLowerCase()) ||
						this.getCommentTargetLabel(comment.target_id, comment.target_type)
							.toLowerCase()
							.includes(this.search.toLowerCase()) ||
						this.childrenComments[comment.id].some((child) => {
							return (
								child.comment_body.toLowerCase().includes(this.search.toLowerCase()) ||
								this.getCommentTargetLabel(child.target_id, child.target_type)
									.toLowerCase()
									.includes(this.search.toLowerCase())
							);
						})
					);
				});
			}

			parentComments.sort((a, b) => {
				return new Date(b.date_time) - new Date(a.date_time);
			});

			return parentComments;
		},
		childrenComments() {
			return this.displayedComments.reduce((acc, comment) => {
				if (parseInt(comment.parent_id) !== 0) {
					if (!acc[comment.parent_id]) {
						acc[comment.parent_id] = [];
					}
					acc[comment.parent_id].push(comment);
				} else {
					if (!acc[comment.id]) {
						acc[comment.id] = [];
					}
				}
				return acc;
			}, {});
		},
		targetLabel() {
			return this.target.id > 0 ? this.getCommentTargetLabel(this.target.id, this.target.type) : '';
		},
	},
};
</script>

<style scoped></style>
