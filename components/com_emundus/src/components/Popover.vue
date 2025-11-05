<template>
	<div :id="id" class="popover-container" @focusout="onFocusOut">
		<button
			v-if="button"
			@click="onClickToggle"
			class="not-to-close-modal tw-flex !tw-w-auto tw-items-center tw-gap-1 tw-rounded-coordinator"
			:class="buttonClass"
			style="padding: 0.5rem"
			:title="button"
		>
			<template v-if="!hideButtonLabel">
				{{ button }}
			</template>
			<span
				v-if="icon"
				class="material-symbols-outlined popover-toggle-btn not-to-close-modal tw-cursor-pointer"
				:class="iconClass"
			>
				{{ icon }}
			</span>
		</button>

		<span
			v-else
			@click="onClickToggle"
			class="material-symbols-outlined popover-toggle-btn not-to-close-modal tw-cursor-pointer tw-rounded-coordinator-form tw-p-1 hover:tw-bg-neutral-300"
			:class="iconClass"
		>
			{{ icon }}
		</span>

		<transition name="fade">
			<div
				v-show="isOpen || absolute"
				class="popover-content tw-rounded-coordinator-form tw-shadow"
				ref="popoverContent"
				:id="'popover-content-' + id"
				:style="popoverContentStyle"
				:class="{
					'tw-z-[-1]': absolute && !isOpen,
					'tw-z-[9999]': (absolute && isOpen) || !absolute,
				}"
				v-click-outside="{
					handler: onFocusOut,
					exclude: ['.not-to-close-modal'],
					disabled: false,
				}"
			>
				<slot></slot>
			</div>
		</transition>
	</div>
</template>

<script>
export default {
	name: 'Popover',
	props: {
		icon: {
			type: String,
			default: 'more_vert',
		},
		button: {
			type: String,
			default: '',
		},
		buttonClass: {
			type: String,
			default: 'tw-btn-primary',
		},
		iconClass: {
			type: String,
			default: '',
		},
		hideButtonLabel: {
			type: Boolean,
			default: false,
		},
		position: {
			type: String,
			default: 'bottom', // top, bottom, left, right
		},
		popoverContentStyle: {
			type: Object,
			default: () => {},
		},
		absolute: {
			type: Boolean,
			default: false,
		},
	},
	data: () => ({
		id: 'popover-' + Math.random().toString(36).substring(2, 9),
		isOpen: false,
		toggleBtnElement: null,
	}),
	created() {
		document.addEventListener('click', this.handleClickOutside);

		// on scroll, if absolute, hide popover
		window.addEventListener('scroll', this.handleScrolling);
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
		window.removeEventListener('scroll', this.handleScrolling);
		// remove popover content from body if absolute
		if (this.absolute) {
			const popoverContentContainer = this.$refs.popoverContent;
			if (popoverContentContainer && popoverContentContainer.parentNode === document.body) {
				document.body.removeChild(popoverContentContainer);
			}
		}
	},
	mounted() {
		this.toggleBtnElement = this.$refs.popoverContent.previousElementSibling;

		if (this.absolute) {
			this.makeContentAbsolute();
		} else {
			this.calculatePosition();
		}
	},
	methods: {
		calculatePosition() {
			const popoverContentContainer = this.$refs.popoverContent;

			if (popoverContentContainer) {
				// get Width and Height of popover content first child
				const popoverContentWidth = popoverContentContainer.children[0].offsetWidth;
				const popoverContentHeight = popoverContentContainer.children[0].offsetHeight;

				if (!this.toggleBtnElement) {
					this.toggleBtnElement = this.$refs.popoverContent.previousElementSibling;
				}

				const popoverToggleBtnWidth = this.toggleBtnElement.offsetWidth;
				const popoverToggleBtnHeight = this.toggleBtnElement.offsetHeight;

				const margin = 4;

				// set position of popover content

				if (!this.absolute) {
					switch (this.position) {
						case 'top':
							// center popover content and make it appear above the toggle button
							popoverContentContainer.style.left = `calc(50% - ${popoverContentWidth / 2}px)`;
							popoverContentContainer.style.bottom = `${popoverToggleBtnHeight + margin}px`;
							break;
						case 'top-left':
							// center popover content and make it appear above the toggle button
							popoverContentContainer.style.right = `0`;
							popoverContentContainer.style.bottom = `${popoverToggleBtnHeight + margin}px`;
							break;
						case 'left':
							// center popover content and make it appear left of the toggle button
							popoverContentContainer.style.top = `calc(50% - ${popoverContentHeight / 2}px)`;
							popoverContentContainer.style.right = `${popoverToggleBtnWidth + margin}px`;
							break;
						case 'right':
							// center popover content and make it appear right of the toggle button
							popoverContentContainer.style.top = `calc(50% - ${popoverContentHeight / 2}px)`;
							popoverContentContainer.style.left = `${popoverToggleBtnWidth + margin}px`;
							break;
						case 'bottom-left':
							// center popover content and make it appear below the toggle button
							popoverContentContainer.style.right = `0`;
							popoverContentContainer.style.top = `${popoverToggleBtnHeight + margin}px`;
							break;
						case 'bottom':
						default:
							// center popover content and make it appear below the toggle button
							popoverContentContainer.style.left = `calc(50% - ${popoverContentWidth / 2}px)`;
							popoverContentContainer.style.top = `${popoverToggleBtnHeight + margin}px`;
							break;
					}
				} else {
					// get position of popover toggle button
					const popoverToggleBtnRect = this.toggleBtnElement.getBoundingClientRect();

					switch (this.position) {
						case 'top':
							// center popover content and make it appear above the toggle button
							popoverContentContainer.style.left = `${popoverToggleBtnRect.left + popoverToggleBtnWidth / 2 - popoverContentWidth / 2}px`;
							popoverContentContainer.style.top = `${popoverToggleBtnRect.top - popoverContentHeight - margin}px`;
							break;
						case 'top-left':
							// center popover content and make it appear above the toggle button
							popoverContentContainer.style.left = `${popoverToggleBtnRect.left}px`;
							popoverContentContainer.style.top = `${popoverToggleBtnRect.top - popoverContentHeight - margin}px`;
							break;
						case 'left':
							// center popover content and make it appear left of the toggle button
							popoverContentContainer.style.left = `${popoverToggleBtnRect.left - popoverContentWidth - margin}px`;
							popoverContentContainer.style.top = `${popoverToggleBtnRect.top + popoverToggleBtnHeight / 2 - popoverContentHeight / 2}px`;
							break;
						case 'right':
							// center popover content and make it appear right of the toggle button
							popoverContentContainer.style.left = `${popoverToggleBtnRect.right + margin}px`;
							popoverContentContainer.style.top = `${popoverToggleBtnRect.top + popoverToggleBtnHeight / 2 - popoverContentHeight / 2}px`;
							break;
						case 'bottom-left':
							// center popover content and make it appear below the toggle button
							popoverContentContainer.style.left = `${popoverToggleBtnRect.left}px`;
							popoverContentContainer.style.top = `${popoverToggleBtnRect.bottom + margin}px`;
							break;
						case 'bottom':
						default:
							// center popover content and make it appear below the toggle button
							popoverContentContainer.style.left = `${popoverToggleBtnRect.left + popoverToggleBtnWidth / 2 - popoverContentWidth / 2}px`;
							popoverContentContainer.style.top = `${popoverToggleBtnRect.bottom + margin}px`;
							break;
					}
				}
			}
		},
		makeContentAbsolute() {
			const popoverContentContainer = this.$refs.popoverContent;
			if (popoverContentContainer) {
				// move popover content to body
				document.body.appendChild(popoverContentContainer);
				this.calculatePosition();
			}
		},
		onClickToggle() {
			this.isOpen = !this.isOpen;

			if (this.isOpen) {
				this.calculatePosition();
			}
		},
		onFocusOut() {
			this.close();
		},
		handleClickOutside(event) {
			const clickedElement = event.target;

			// if clicked element is not inside this component then close popover
			if (!clickedElement.closest('#' + this.id)) {
				this.close();
			}
		},
		handleScrolling() {
			if (this.absolute && this.isOpen) {
				this.close();
			}
		},
		close() {
			this.isOpen = false;
		},
	},
};
</script>

<style scoped>
.popover-container {
	position: relative;
	display: inline-block;
}

.popover-content {
	background-color: white;
	position: absolute;
	min-height: 40px;
	min-width: 100px;
	width: max-content;
	opacity: 1;
	transition: opacity 0.2s ease-in-out;
}

.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}
</style>
