<script>
import Modal from '@/components/Modal.vue';
import LocationForm from '@/views/Events/LocationForm.vue';

export default {
	name: 'LocationPopup',
	components: { LocationForm, Modal },
	props: {
		location_id: {
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
		closeModal(location_id) {
			this.$emit('close', location_id);
		},
	},
};
</script>

<template>
	<modal
		:name="'add-location-modal'"
		:classes="' tw-max-h-[80vh] tw-overflow-y-auto tw-rounded tw-px-4 tw-shadow-modal'"
		transition="nice-modal-fade"
		:width="'600px'"
		:delay="100"
		:adaptive="true"
		:clickToClose="false"
		:blockScrolling="true"
		@closed="beforeClose"
		@before-open="beforeOpen"
	>
		<LocationForm :is-modal="true" :id="location_id" @close="closeModal" />
	</modal>
</template>

<style scoped>
@import '../../../assets/css/modal.scss';

.placement-center {
	position: fixed;
	left: 50%;
	transform: translate(-50%, -50%);
	top: 50%;
}
</style>
