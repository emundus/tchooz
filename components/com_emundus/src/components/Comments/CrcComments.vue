<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Chip from '@/components/Atoms/Chip.vue';
import commentsService from '@/services/comments.js';
import Popover from '@/components/Popover.vue';
import alerts from '@/mixins/alerts.js';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	name: 'CrcComments',
	components: { Loader, Popover, Parameter, Chip },
	emits: ['update-items'],
	mixins: [alerts],
	props: {
		item: {
			type: Object,
			required: true,
		},
		targetType: {
			type: String,
			required: true,
			validator: (value) => ['contact', 'organization'].includes(value),
		},
	},
	data() {
		return {
			loading: false,
			showForm: false,
			saving: false,
			replyToCommentId: null,
			currentUserId: null,
			searchQuery: '',
			editingCommentId: null,
			openedThreadId: null,
			fields: [
				{
					param: 'comment_body',
					type: 'textarea',
					placeholder: 'COM_EMUNDUS_ONBOARD_CRC_COMMENT_PLACEHOLDER',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_CRC_COMMENT_LABEL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'visible_to_applicant',
					type: 'toggle',
					value: '0',
					label: 'COM_EMUNDUS_ONBOARD_CRC_COMMENT_VISIBILITY',
					displayed: true,
					optional: true,
				},
			],
			alreadyLoaded: false,
		};
	},
	created() {
		const userDetails = Joomla.getOptions('plg_system_emundus.user_details');
		this.currentUserId = userDetails?.id || userDetails?.user_id || null;

		if (Swal.isVisible()) {
			Swal.close();
		}

		const commentsLength = this.item.comments?.length || 0;
		if (commentsLength === 0 && !this.alreadyLoaded) {
			this.getCommentsByTarget();
		}
	},
	methods: {
		getCommentsByTarget() {
			this.loading = true;

			commentsService
				.getCommentsByTarget(this.targetType, this.item.id)
				.then((response) => {
					if (response.status) {
						this.alreadyLoaded = true;
						this.item.comments = response.data;
					} else {
						// todo: alert msg
					}
				})
				.catch((error) => {
					// todo: alert msg
				})
				.finally(() => {
					this.loading = false;
				});
		},
		isEdited(comment) {
			return !!comment.updated;
		},
		openCommentForm() {
			this.replyToCommentId = null;
			this.editingCommentId = null;
			this.showForm = true;
		},
		cancelForm() {
			this.showForm = false;
			this.replyToCommentId = null;
			this.editingCommentId = null;
			this.resetFields();
		},
		resetFields() {
			this.fields.forEach((field) => {
				field.value = field.param === 'visible_to_applicant' ? '0' : '';
			});
		},
		isThreadOpen(commentId) {
			return this.openedThreadId === commentId || this.isSearching;
		},
		toggleThread(commentId) {
			this.openedThreadId = this.openedThreadId === commentId ? null : commentId;
		},
		startReply(commentId) {
			this.replyToCommentId = commentId;
			this.editingCommentId = null;
			this.openedThreadId = commentId;

			const parentComment = this.comments.find((c) => c.id === commentId);
			if (parentComment && Number(parentComment.is_public) === 0) {
				this.fields.find((f) => f.param === 'visible_to_applicant').value = '0';
			}

			this.showForm = true;

			this.$nextTick(() => {
				this.$refs.formContainer?.scrollIntoView({
					behavior: 'smooth',
					block: 'center',
				});
			});
		},
		startEdit(comment) {
			this.replyToCommentId = null;
			this.editingCommentId = comment.id;

			if (comment.parent_id) {
				this.openedThreadId = comment.parent_id;
			}

			this.fields.find((f) => f.param === 'comment_body').value = comment.comment_body || '';
			this.fields.find((f) => f.param === 'visible_to_applicant').value = Number(comment.is_public) === 1 ? '1' : '0';

			this.showForm = true;

			this.$nextTick(() => {
				this.$refs.formContainer?.scrollIntoView({
					behavior: 'smooth',
					block: 'center',
				});
			});
		},
		isCurrentUserCommentOwner(comment) {
			return this.currentUserId && Number(comment.user_id) === Number(this.currentUserId);
		},
		isPublicComment(comment) {
			return Number(comment.is_public) === 1;
		},
		formatCommentDate(dateStr) {
			if (!dateStr) return '';
			const date = new Date(dateStr);
			if (isNaN(date.getTime())) return '';

			const d = String(date.getDate()).padStart(2, '0');
			const mo = String(date.getMonth() + 1).padStart(2, '0');
			const y = date.getFullYear();
			const h = String(date.getHours()).padStart(2, '0');
			const mi = String(date.getMinutes()).padStart(2, '0');

			return `${d}/${mo}/${y} ${this.translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DATE_AT')} ${h}:${mi}`;
		},
		isFormValid() {
			return this.fields.every((field) => this.validateField(field.param));
		},

		validateField(param) {
			const ref = this.$refs['field_' + param];
			const component = Array.isArray(ref) ? ref[0] : ref;

			if (!component?.validate) return true;

			return component.validate();
		},

		async submitComment() {
			const body = (this.commentBody || '').trim();
			if (!body || this.saving) return;

			if (!this.isFormValid()) return;

			this.saving = true;

			let comment = {};
			comment.targetType = this.targetType;
			comment.targetId = this.item.id;
			comment.content = body;
			comment.isPublic = parseInt(this.fields.find((f) => f.param === 'visible_to_applicant').value) === 1 ? 1 : 0;

			let parentId = null;

			if (this.replyToCommentId) {
				comment.parentId = this.replyToCommentId;
				parentId = this.replyToCommentId;
			}

			if (this.editingCommentId) {
				comment.id = this.editingCommentId;
				const editedComment = this.comments.find((c) => c.id === this.editingCommentId);
				if (editedComment && editedComment.parent_id) {
					parentId = editedComment.parent_id;
				}
			}

			if (parentId) {
				const parent = this.comments.find((c) => c.id === parentId);
				if (parent && Number(parent.is_public) === 0) {
					comment.isPublic = 0;
				}
			}

			try {
				const response = await commentsService.saveComment(comment);
				if (response.status) {
					const wasEditing = this.isEditing;
					this.cancelForm();
					this.alertSuccess(
						wasEditing
							? 'COM_EMUNDUS_ONBOARD_CRC_COMMENT_UPDATE_SUCCESS'
							: 'COM_EMUNDUS_ONBOARD_CRC_COMMENT_SAVE_SUCCESS',
					);

					this.$emit('update-items');
				} else {
					this.alertError('Oops...', response.msg || this.translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_SAVE_ERROR'));
				}
			} finally {
				this.saving = false;
			}
		},
		async deleteComment(commentId) {
			const result = await Swal.fire({
				icon: 'warning',
				title: this.translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETE_CONFIRM_TITLE'),
				text: this.translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETE_CONFIRM_TEXT'),
				showCancelButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
				cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					cancelButton: 'em-swal-cancel-button',
					actions: 'em-swal-double-action',
				},
				didOpen: () => {
					document.querySelector('.swal2-container').style.setProperty('z-index', '999999999', 'important');
				},
			});

			if (!result.isConfirmed) return;

			try {
				const response = await commentsService.removeComment(commentId);
				if (response.status) {
					this.alertSuccess('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETE_SUCCESS');
					this.$emit('update-items');
				} else {
					this.alertError('Oops...', response.msg || this.translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETE_ERROR'));
				}
			} catch (e) {
				this.alertError('Oops...', this.translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETE_ERROR'));
			}
		},
	},
	computed: {
		editingTargetComment() {
			if (!this.editingCommentId) return null;
			return this.comments.find((c) => c.id === this.editingCommentId);
		},
		isEditing() {
			return this.editingCommentId !== null;
		},
		commentBody() {
			return this.fields.find((field) => field.param === 'comment_body').value;
		},
		canSubmit() {
			return !this.saving && !!this.commentBody && !!this.commentBody.trim();
		},
		comments() {
			return this.item.comments || [];
		},
		hasComments() {
			return this.comments.length > 0;
		},
		replyTargetComment() {
			if (!this.replyToCommentId) return null;
			return this.comments.find((c) => c.id === this.replyToCommentId);
		},
		filteredComments() {
			if (!this.searchQuery.trim()) return this.comments;

			const query = this.searchQuery.toLowerCase().trim();
			const matchingComments = this.comments.filter((comment) => {
				return comment.comment_body?.toLowerCase().includes(query);
			});

			const matchingIds = new Set(matchingComments.map((c) => c.id));
			const parentIdsToInclude = new Set();
			const childIdsToInclude = new Set();

			for (const comment of matchingComments) {
				if (comment.parent_id) {
					parentIdsToInclude.add(comment.parent_id);
					childIdsToInclude.add(comment.id);
				} else {
					parentIdsToInclude.add(comment.id);
				}
			}

			for (const comment of this.comments) {
				if (parentIdsToInclude.has(comment.parent_id) && matchingIds.has(comment.parent_id)) {
					childIdsToInclude.add(comment.id);
				}
			}

			return this.comments.filter((comment) => parentIdsToInclude.has(comment.id) || childIdsToInclude.has(comment.id));
		},
		formattedComments() {
			const parentComments = this.filteredComments.filter((comment) => !comment.parent_id);
			const parentIds = new Set(parentComments.map((c) => c.id));

			const repliesByParentId = {};
			const orphanReplies = [];

			for (const comment of this.filteredComments) {
				if (comment.parent_id) {
					if (parentIds.has(comment.parent_id)) {
						if (!repliesByParentId[comment.parent_id]) {
							repliesByParentId[comment.parent_id] = [];
						}
						repliesByParentId[comment.parent_id].push(comment);
					} else {
						orphanReplies.push(comment);
					}
				}
			}

			const orphansByDeletedParentId = {};
			for (const reply of orphanReplies) {
				if (!orphansByDeletedParentId[reply.parent_id]) {
					orphansByDeletedParentId[reply.parent_id] = [];
				}
				orphansByDeletedParentId[reply.parent_id].push(reply);
			}

			const formatted = parentComments.map((parentComment) => ({
				...parentComment,
				replies: repliesByParentId[parentComment.id] || [],
			}));

			for (const [deletedParentId, replies] of Object.entries(orphansByDeletedParentId)) {
				formatted.push({
					id: `deleted-${deletedParentId}`,
					isDeletedPlaceholder: true,
					comment_body: '',
					name: '',
					date: null,
					is_public: replies[0]?.is_public ?? 1,
					user_id: null,
					parent_id: 0,
					replies: replies,
				});
			}

			return formatted;
		},
		hasSearchResults() {
			return this.formattedComments.length > 0;
		},
		isSearching() {
			return this.searchQuery.trim().length > 0;
		},
		shouldVisibilityBeForced() {
			let parentId = null;

			if (this.replyToCommentId) {
				parentId = this.replyToCommentId;
			} else if (this.editingCommentId) {
				const editedComment = this.comments.find((c) => c.id === this.editingCommentId);
				if (editedComment && editedComment.parent_id) {
					parentId = editedComment.parent_id;
				}
			}

			if (!parentId) return false;

			const parent = this.comments.find((c) => c.id === parentId);
			return parent ? Number(parent.is_public) === 0 : false;
		},
	},
	watch: {
		shouldVisibilityBeForced(newVal) {
			const visibilityField = this.fields.find((f) => f.param === 'visible_to_applicant');
			if (visibilityField) {
				visibilityField.displayed = !newVal;
			}
		},
	},
};
</script>

<template>
	<div>
		<div v-if="!loading" class="tw-flex tw-flex-col tw-gap-4">
			<div>
				<div v-if="hasComments" class="tw-mb-3">
					<div class="tw-relative">
						<input
							v-model="searchQuery"
							type="text"
							class="tw-w-full tw-rounded-md tw-border tw-border-neutral-300 tw-bg-white tw-py-2 tw-pl-10 tw-pr-3 focus:tw-border-neutral-500 focus:tw-outline-none"
							:placeholder="translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_SEARCH_PLACEHOLDER')"
						/>
						<button
							v-if="searchQuery"
							type="button"
							class="tw-absolute tw-right-3 tw-top-1/2 -tw-translate-y-1/2 tw-cursor-pointer tw-bg-transparent tw-text-neutral-500"
							@click="searchQuery = ''"
						>
							<span class="material-symbols-outlined">close</span>
						</button>
					</div>
				</div>

				<div v-if="!hasComments" class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-8">
					<span class="material-symbols-outlined tw-text-5xl tw-text-neutral-600">chat</span>
					<p class="tw-mt-2 tw-text-neutral-500">
						{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENTS_PLACEHOLDER') }}
					</p>
				</div>

				<div
					v-else-if="isSearching && !hasSearchResults"
					class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-8"
				>
					<span class="material-symbols-outlined tw-text-5xl tw-text-neutral-600">search_off</span>
					<p class="tw-mt-2 tw-text-neutral-500">
						{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_SEARCH_NO_RESULTS') }}
					</p>
				</div>

				<div v-else class="tw-flex tw-flex-col tw-gap-3">
					<div v-for="comment in formattedComments" :key="comment.id">
						<div v-if="comment.isDeletedPlaceholder" class="tw-rounded-coordinator tw-border tw-p-3">
							<p class="tw-italic tw-text-neutral-500">
								{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETED_PLACEHOLDER') }}
							</p>
						</div>
						<div
							v-else
							class="tw-rounded-t-lg tw-border tw-p-3"
							:class="
								isCurrentUserCommentOwner(comment)
									? 'tw-rounded-bl-lg tw-border-profile-full tw-bg-profile-light'
									: 'tw-rounded-br-lg tw-border-neutral-300 tw-bg-white'
							"
						>
							<div class="tw-flex tw-items-center tw-justify-between tw-gap-2">
								<div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
									<Chip :text="comment.name" class="!tw-mb-0 !tw-mr-0" />
									<span v-if="comment.date" class="tw-text-sm tw-leading-none tw-text-neutral-500">
										{{ formatCommentDate(comment.date) }}
									</span>
								</div>
								<div class="tw-flex tw-items-center tw-gap-2">
									<span
										v-if="!isPublicComment(comment)"
										class="material-symbols-outlined tw-text-neutral-500"
										:title="translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_VISIBILITY_PRIVATE')"
									>
										visibility_off
									</span>

									<div class="tw-relative">
										<popover
											:hideButtonLabel="true"
											:button-class="'tw-btn-secondary tw-h-form'"
											:icon="'more_vert'"
											:position="'bottom-left'"
											class="custom-popover-arrow"
										>
											<div class="tw-flex tw-flex-col tw-gap-1 tw-p-1">
												<button
													type="button"
													class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-gap-2 tw-rounded tw-bg-transparent tw-px-3 tw-py-2 tw-text-left hover:tw-bg-neutral-100"
													@click="startReply(comment.id)"
												>
													<span class="material-symbols-outlined">reply</span>
													{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_REPLY') }}
												</button>
												<button
													v-if="isCurrentUserCommentOwner(comment)"
													type="button"
													class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-gap-2 tw-rounded tw-bg-transparent tw-px-3 tw-py-2 tw-text-left hover:tw-bg-neutral-100"
													@click="startEdit(comment)"
												>
													<span class="material-symbols-outlined">edit</span>
													{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDIT') }}
												</button>
												<button
													v-if="isCurrentUserCommentOwner(comment)"
													type="button"
													class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-gap-2 tw-rounded tw-bg-transparent tw-px-3 tw-py-2 tw-text-left tw-text-red-500 hover:tw-bg-neutral-100"
													@click="deleteComment(comment.id)"
												>
													<span class="material-symbols-outlined tw-text-red-500"> delete </span>
													{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETE') }}
												</button>
											</div>
										</popover>
									</div>
								</div>
							</div>

							<div class="tw-mt-2 tw-flex tw-items-end tw-justify-between tw-gap-3">
								<div class="tw-flex tw-flex-col tw-gap-1">
									<p class="tw-whitespace-pre-wrap tw-text-neutral-900">
										{{ comment.comment_body }}
									</p>
									<span
										v-if="isEdited(comment)"
										class="tw-text-sm tw-italic tw-text-neutral-500"
										:title="
											translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDITED_AT') + ' ' + formatCommentDate(comment.updated)
										"
									>
										{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDITED') }}
									</span>
								</div>
								<button
									v-if="comment.replies.length > 0 && !comment.isDeletedPlaceholder"
									type="button"
									class="tw-flex tw-shrink-0 tw-cursor-pointer tw-items-center tw-gap-1 tw-rounded-coordinator tw-border tw-border-profile-full tw-bg-neutral-200 tw-px-2 tw-py-1 tw-text-xs hover:tw-bg-neutral-300"
									@click="toggleThread(comment.id)"
									:title="
										isThreadOpen(comment.id)
											? translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_HIDE_REPLIES')
											: translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_SHOW_REPLIES')
									"
								>
									<span
										class="tw-flex tw-h-5 tw-w-5 tw-items-center tw-justify-center tw-rounded-full tw-bg-profile-full tw-text-xs tw-text-white"
									>
										{{ comment.replies.length }}
									</span>
									{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_REPLIES') }}
									<span class="material-symbols-outlined" style="font-size: 1.125rem">
										{{ isThreadOpen(comment.id) ? 'expand_less' : 'expand_more' }}
									</span>
								</button>
							</div>
						</div>

						<div
							v-if="comment.replies.length > 0 && (comment.isDeletedPlaceholder || isThreadOpen(comment.id))"
							class="tw-mt-2 tw-flex tw-flex-col tw-gap-2"
						>
							<div v-for="reply in comment.replies" :key="reply.id" class="tw-flex tw-items-stretch tw-gap-3">
								<div class="tw-w-0.5 tw-shrink-0 tw-bg-neutral-300"></div>
								<div
									class="tw-flex-1 tw-rounded-t-lg tw-border tw-p-3"
									:class="
										isCurrentUserCommentOwner(reply)
											? 'tw-rounded-bl-lg tw-border-profile-full tw-bg-profile-light'
											: 'tw-rounded-br-lg tw-border-neutral-300 tw-bg-white'
									"
								>
									<div class="tw-flex tw-items-center tw-justify-between tw-gap-2">
										<div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
											<Chip :text="reply.name" class="!tw-mb-0 !tw-mr-0" />
											<span v-if="reply.date" class="tw-text-sm tw-leading-none tw-text-neutral-500">
												{{ formatCommentDate(reply.date) }}
											</span>
										</div>
										<div class="tw-flex tw-items-center tw-gap-2">
											<span v-if="!isPublicComment(reply)" class="material-symbols-outlined tw-text-neutral-500">
												visibility_off
											</span>

											<div v-if="isCurrentUserCommentOwner(reply)" class="tw-relative">
												<popover
													:hideButtonLabel="true"
													:button-class="'tw-btn-secondary tw-h-form'"
													:icon="'more_vert'"
													:position="'bottom-left'"
													class="custom-popover-arrow"
												>
													<div class="tw-flex tw-flex-col tw-gap-1 tw-p-1">
														<button
															type="button"
															class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-gap-2 tw-rounded tw-bg-transparent tw-px-3 tw-py-2 tw-text-left hover:tw-bg-neutral-100"
															@click="startEdit(reply)"
														>
															<span class="material-symbols-outlined">edit</span>
															<span>{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDIT') }}</span>
														</button>
														<button
															type="button"
															class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-gap-2 tw-rounded tw-bg-transparent tw-px-3 tw-py-2 tw-text-left tw-text-red-500 hover:tw-bg-neutral-100"
															@click="deleteComment(reply.id)"
														>
															<span class="material-symbols-outlined tw-text-red-500">delete</span>
															{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_DELETE') }}
														</button>
													</div>
												</popover>
											</div>
										</div>
									</div>
									<div class="tw-mt-2 tw-flex tw-flex-col tw-gap-1">
										<p class="tw-whitespace-pre-wrap tw-text-neutral-900">
											{{ reply.comment_body }}
										</p>
										<span
											v-if="isEdited(reply)"
											class="tw-text-sm tw-italic tw-text-neutral-500"
											:title="
												translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDITED_AT') + ' ' + formatCommentDate(reply.updated)
											"
										>
											{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDITED') }}
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div
				v-if="showForm"
				ref="formContainer"
				class="tw-flex tw-flex-col tw-gap-4 tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-4"
			>
				<div
					v-if="replyTargetComment"
					class="tw-flex tw-items-center tw-justify-between tw-gap-2 tw-rounded-md tw-bg-neutral-100 tw-px-3 tw-py-2"
				>
					<div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-neutral-700">
						<span class="material-symbols-outlined" style="font-size: 1.125rem">reply</span>
						<span>
							{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_REPLYING_TO') }}
							<strong>{{ replyTargetComment.name }}</strong>
						</span>
					</div>
					<button
						type="button"
						class="tw-cursor-pointer tw-bg-transparent tw-text-neutral-500"
						@click="replyToCommentId = null"
						:title="translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_CANCEL_REPLY')"
					>
						<span class="material-symbols-outlined" style="font-size: 1.125rem">close</span>
					</button>
				</div>
				<div
					v-if="editingTargetComment"
					class="tw-flex tw-items-center tw-justify-between tw-gap-2 tw-rounded-md tw-bg-neutral-100 tw-px-3 tw-py-2"
				>
					<div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-neutral-700">
						<span class="material-symbols-outlined" style="font-size: 1.125rem">edit</span>
						<span>{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDITING') }}</span>
					</div>
					<button
						type="button"
						class="tw-cursor-pointer tw-bg-transparent tw-text-neutral-500"
						@click="cancelForm"
						:title="translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_CANCEL_EDIT')"
					>
						<span class="material-symbols-outlined" style="font-size: 1.125rem">close</span>
					</button>
				</div>

				<div
					v-for="field in fields"
					:key="field.param"
					v-show="field.displayed"
					:class="field.width ? field.width : 'tw-w-full'"
				>
					<Parameter
						:ref="'field_' + field.param"
						:parameter-object="field"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
						@value-updated="onParameterUpdated"
					/>
				</div>

				<div class="tw-mt-2 tw-flex tw-items-center tw-justify-between">
					<button
						type="button"
						class="tw-btn-secondary tw-flex !tw-w-auto tw-items-center tw-gap-2"
						:disabled="saving"
						@click="cancelForm"
					>
						<span class="material-symbols-outlined">cancel</span>
						{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_CANCEL') }}
					</button>
					<button
						type="button"
						class="tw-btn-primary tw-flex !tw-w-auto tw-items-center tw-gap-2"
						:disabled="!canSubmit"
						@click="submitComment"
					>
						<span class="material-symbols-outlined">{{ isEditing ? 'edit' : 'send' }}</span>
						<span v-if="!saving">
							{{
								isEditing
									? translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_EDIT_SUBMIT')
									: translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_SUBMIT')
							}}
						</span>
						<span v-else>{{ translate('COM_EMUNDUS_LOADING') }}</span>
					</button>
				</div>
			</div>

			<div v-if="!showForm" class="tw-flex tw-justify-center">
				<button
					type="button"
					class="tw-btn-primary tw-flex !tw-w-auto tw-items-center tw-gap-2"
					@click="openCommentForm"
				>
					<span class="material-symbols-outlined">add_comment</span>
					{{ translate('COM_EMUNDUS_ONBOARD_CRC_COMMENT_ADD') }}
				</button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<style scoped></style>
