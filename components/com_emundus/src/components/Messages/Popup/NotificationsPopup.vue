<script>
/* Services */
import messengerServices from '@/services/messenger';

export default {
	name: 'NotificationsPopup',
	props: {
		unreadMessages: {
			type: Array,
			required: true,
		},
	},
	data: () => ({
		conversionOpened: 0,
	}),
	created() {
		document.addEventListener('click', this.handleClickOutside);
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},
	methods: {
		toggleConversation(id) {
			if (this.conversionOpened === id) {
				this.conversionOpened = 0;
			} else {
				this.conversionOpened = id;
			}
		},

		closeChatroom(fnum) {
			messengerServices.closeChatroom(fnum).then((response) => {
				if (response.status) {
					this.$emit('closedChatroom', fnum);
				} else {
					Swal.fire({
						title: Joomla.Text._('COM_EMUNDUS_ONBOARD_ERROR'),
						text: response.msg,
						type: 'error',
						showCancelButton: false,
						showConfirmButton: false,
						timer: 3000,
					});
				}
			});
		},

		goToFile(fnum) {
			messengerServices.goToFile(fnum).then((response) => {
				if (response.status) {
					window.location.href = response.route;
					let event = new CustomEvent('messengerOpenFile', { detail: { fnum: fnum } });
					document.dispatchEvent(event);
				}
			});
		},

		sentTranslation(unread) {
			if (unread.messages.length > 1) {
				return this.translate('COM_EMUNDUS_MESSENGER_NOTIFICATIONS_HAS_SENT_MESSAGES').replace(
					'%count',
					unread.messages.length,
				);
			} else {
				return this.translate('COM_EMUNDUS_MESSENGER_NOTIFICATIONS_HAS_SENT_ONE_MESSAGE');
			}
		},

		handleClickOutside(event) {
			const clickedElement = event.target;

			// if clicked element is not inside this component then close popover
			if (!clickedElement.closest('#messenger_notifications_popup')) {
				this.$emit('close');
			}
		},
	},
};
</script>

<template>
	<div class="tw-relative">
		<div
			class="tw-absolute tw-bg-white tw-rounded-coordinator tw-p-3 tw-right-0 tw-top-6 tw-w-[25em] tw-shadow-standard tw-border tw-border-neutral-300"
		>
			<div class="tw-flex tw-items-center tw-justify-between">
				<h4>{{ translate('COM_EMUNDUS_MESSENGER_NOTIFICATIONS') }}</h4>
				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>

			<hr />

			<div class="tw-overflow-auto tw-max-h-[20em] tw-flex tw-flex-col tw-gap-3" v-if="unreadMessages.length > 0">
				<div v-for="unread in unreadMessages" class="tw-flex tw-flex-col">
					<div class="tw-flex tw-items-center tw-gap-1 tw-flex-wrap tw-overflow-x-hidden">
						<label class="tw-flex tw-gap-1 tw-items-center tw-text-base tw-cursor-pointer tw-mb-0">
							<a type="button" class="tw-cursor-pointer tw-text-blue-500" @click="goToFile(unread.fnum)">{{
								unread.fullname
							}}</a>
							<span @click="toggleConversation(unread.page)">{{ sentTranslation(unread) }}</span>
						</label>
						<span
							class="material-symbols-outlined tw-cursor-pointer"
							@click="toggleConversation(unread.page)"
							:class="{ 'tw-rotate-90': conversionOpened === unread.page }"
							>chevron_right</span
						>
					</div>

					<div class="tw-mt-1 tw-pl-1 tw-border-s-2 tw-border-main-500" v-if="conversionOpened === unread.page">
						<div v-for="message in unread.messages" class="tw-flex tw-flex-col tw-gap-1 tw-mb-2">
							<span class="tw-text-sm">{{ message.date_time }}</span>
							<div class="tw-p-2 tw-border tw-border-neutral-300 tw-rounded-coordinator">
								{{ message.message }}
							</div>
						</div>

						<div class="tw-mt-1 tw-mx-1 tw-flex tw-justify-between tw-items-center">
							<button type="button" class="tw-cursor-pointer tw-text-blue-500" @click="closeChatroom(unread.fnum)">
								{{ translate('COM_EMUNDUS_MESSENGER_CLOSE_CHATROOM') }}
							</button>
							<span class="tw-cursor-pointer material-symbols-outlined" @click="goToFile(unread.fnum)">reply</span>
						</div>
					</div>
				</div>
			</div>

			<div v-else>
				{{ translate('COM_EMUNDUS_MESSENGER_NO_NOTIFICATIONS') }}
			</div>
		</div>
	</div>
</template>

<style scoped></style>
