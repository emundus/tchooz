<script>
import Modal from '@/components/Modal.vue';
import ContactForm from '@/views/Contacts/ContactForm.vue';

export default {
	name: 'ContactPopup',
	components: { ContactForm, Modal },
	props: {
		contact_id: {
			type: Number,
			default: 0,
		},
	},
	emits: ['close', 'open'],
	methods: {
		beforeClose() {
			this.$emit('close');
		},
		beforeOpen() {
			this.$emit('open');
		},
		closeModal(contact_id) {
			this.$emit('close', contact_id);
		},
	},
};
</script>

<template>
	<modal
		:name="'add-contact-modal'"
		:class="'placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-rounded-2xl tw-p-8 tw-shadow-modal'"
		transition="nice-modal-fade"
		:width="'600px'"
		:delay="100"
		:adaptive="true"
		:clickToClose="false"
		@closed="beforeClose"
		@before-open="beforeOpen"
	>
		<ContactForm :is-modal="true" :id="contact_id" @close="closeModal" />
	</modal>
</template>

<style scoped>
@import '../../assets/css/modal.scss';

.placement-center {
	position: fixed;
	left: 50%;
	transform: translate(-50%, -50%);
	top: 50%;
}
</style>
