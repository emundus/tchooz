<template>
	<transition :name="transition" :duration="delay">
		<div class="modal___wrapper" v-show="isOpened">
			<div class="modal___backdrop"></div>
			<div
				:id="'modal___' + name"
				class="modal___container"
				:class="classes"
				ref="modal_container"
				v-click-outside="{
					handler: onFocusOut,
					exclude: ['.not-to-close-modal'],
					disabled: false,
				}"
			>
				<h1 v-if="title.length > 0" class="tw-text-center">{{ translate(title) }}</h1>
				<slot @close="close"> </slot>
			</div>
		</div>
	</transition>
</template>

<script>
export default {
	props: {
		name: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			default: '',
		},
		width: {
			type: String,
			default: '100%',
		},
		height: {
			type: String,
			default: 'auto',
		},
		transition: {
			type: String,
			default: 'fade',
		},
		top: {
			type: String,
			default: '0',
		},
		left: {
			type: String,
			default: '0',
		},
		center: {
			type: Boolean,
			default: true,
		},
		delay: {
			type: Number,
			default: 0,
		},
		clickToClose: {
			type: Boolean,
			default: true,
		},
		openOnCreate: {
			type: Boolean,
			default: true,
		},
		classes: {
			type: String,
			default: '',
		},
	},
	emits: ['beforeOpen', 'closed'],
	data() {
		return {
			isOpened: false,
		};
	},
	mounted() {
		if (this.openOnCreate) {
			this.open();
		}
	},
	methods: {
		open() {
			this.$emit('beforeOpen');
			this.isOpened = true;

			this.$refs.modal_container.style.width = this.width;
			this.$refs.modal_container.style.height = this.height;
			this.$refs.modal_container.style.zIndex = 999999;
			this.$refs.modal_container.style.opacity = 1;
			this.$refs.modal_container.style.top = this.top;
			this.$refs.modal_container.style.left = this.left;

			if (this.center) {
				this.$refs.modal_container.style.transform = 'translate(-50%, -50%)';
				this.$refs.modal_container.style.top = '50%';
				this.$refs.modal_container.style.left = '50%';
			} else {
				this.$refs.modal_container.style.transform = 'none';
			}
		},
		close() {
			this.isOpened = false;
			this.$refs.modal_container.style.zIndex = -999999;
			this.$refs.modal_container.style.opacity = 0;

			this.$emit('closed');
		},
		onFocusOut() {
			if (this.clickToClose) {
				this.isOpened = false;
				this.close();
			}
		},
	},
};
</script>

<style scoped>
.modal___wrapper {
	position: fixed;
	top: 0;
	left: 0;
	width: 100vw;
	height: 100vh;
	z-index: 999999;
	display: flex;
	justify-content: center;
	align-items: center;
	transition:
		opacity 0.3s,
		visibility 0.3s;
	overflow-y: auto;
}

.modal___backdrop {
	position: fixed;
	top: 0;
	left: 0;
	width: 100vw;
	height: 100vh;
	z-index: 999998;
	background: rgba(0, 0, 0, 0.2);
	backdrop-filter: blur(1px) opacity(1);
	transition: 0.3s;
}

.modal___container {
	position: fixed;
	top: 0;
	left: 0;
	z-index: -999999;
	width: 0;
	height: 0;
	background-color: white;
	opacity: 0;
}
</style>
