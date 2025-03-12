<template>
	<div>
		<MessengerPopup
			v-if="modalOpened && applicant"
			@close="modalOpened = false"
			:fnum="fnum"
			:fullname="fullname"
			:unread_messages="notifications"
			@closedChatroom="closedChatroom"
		/>

		<div id="messenger_notifications_popup">
			<NotificationsPopup
				v-if="modalOpened && !applicant"
				@close="modalOpened = false"
				:unread-messages="notifications"
				@closedChatroom="closedChatroom"
			/>

			<div class="tw-relative" style="height: 20px" id="messenger_notifications_icon">
				<span
					class="material-symbols-outlined tw-cursor-pointer tw-text-neutral-900"
					@click="modalOpened = !modalOpened"
					>question_answer</span
				>
				<div
					class="tw-absolute tw-rounded-full tw-bg-red-500"
					style="top: -2px; right: 4px; width: 8px; height: 8px"
					v-if="notifications.length > 0"
				></div>
			</div>
		</div>
	</div>
</template>

<script>
import { ref } from 'vue';

import MessengerPopup from '@/components/Messages/Popup/MessengerPopup.vue';
import NotificationsPopup from '../../components/Messages/Popup/NotificationsPopup.vue';

export default {
	name: 'Messenger',
	props: {
		fnum: {
			type: String,
			required: true,
		},
		fullname: {
			type: String,
			required: true,
		},
		unread_messages: {
			type: Array,
		},
		applicant: {
			type: Boolean,
			default: true,
		},
	},
	components: {
		NotificationsPopup,
		MessengerPopup,
	},
	data() {
		return {
			counter: 0,
			notifications: [],

			modalOpened: false,
		};
	},
	created() {
		if (!this.applicant) {
			document.addEventListener('removeMessengerNotifications', this.removeNotifications);
		}
		this.notifications = ref(this.unread_messages);
	},
	beforeUnmount() {
		document.removeEventListener('removeMessengerNotifications', this.removeNotifications);
	},
	methods: {
		removeNotifications(event) {
			if (event.detail) {
				this.closedChatroom(event.detail.fnum);
			}
		},
		closedChatroom(fnum) {
			// Filter notifications array
			this.notifications = this.notifications.filter((notification) => notification.fnum !== fnum);
		},
	},
};
</script>

<style>
#app {
	font-family: Avenir, Helvetica, Arial, sans-serif;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	color: #2c3e50;
}

.messages__vue {
	min-height: unset !important;
}
</style>
