import { defineStore } from 'pinia';
export const useAttachmentStore = defineStore('attachment', {
	state: () => ({
		attachmentPath: 'images/emundus/files/',
		attachments: {},
		selectedAttachment: {},
		previews: {},
		categories: {},
		checkedAttachments: [],
	}),
	actions: {
		setAttachments(attachments) {
			this.attachments = attachments;
		},
		setAttachmentsOfFnum(fnum, attachments) {
			this.attachments[fnum] = attachments;
		},
		updateAttachmentOfFnum(data) {
			const attachmentIndex = this.attachments[data.fnum].findIndex(
				(attachment) => attachment.aid === data.attachment.aid,
			);

			this.attachments[data.fnum][attachmentIndex] = data.attachment;
		},
		setSelectedAttachment(attachment) {
			this.selectedAttachment = attachment;
		},
		setPreview(previewData) {
			this.previews[previewData.id] = previewData.preview;
		},
		setCategories(categories) {
			this.categories = categories;
		},
		setAttachmentPath(path) {
			this.attachmentPath = path;
		},
		setCheckedAttachments(attachments) {
			this.checkedAttachments = attachments;
		},
	},
});
