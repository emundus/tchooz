<script>
import { Button, FormField, Icon } from '@emundus/ui';

import resourceService from '@/services/resource.js';

import alerts from '@/mixins/alerts.js';

/**
 * "Share link" tab of the "Access & sharing" modal. Owns the expiration date,
 * an optional password and the read-only share code. Exposes save() so the
 * parent modal's single "Enregistrer" button can persist it.
 */
export default {
	name: 'ShareLinkTab',
	components: { Button, FormField, Icon },
	mixins: [alerts],
	props: {
		resourceId: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			code: '',
			hasPassword: false,
			expirationDate: '',
			password: '',
			isLoading: true,
			isRevoking: false,
		};
	},
	computed: {
		hasLink() {
			return this.code !== '';
		},
		// Full public link consumed by the resource `viewshared` endpoint.
		shareUrl() {
			if (!this.code) {
				return '';
			}

			const base = window.location.origin;
			return `${base}/index.php?option=com_emundus&controller=resource&task=viewshared&code=${encodeURIComponent(this.code)}`;
		},
	},
	created() {
		this.load();
	},
	methods: {
		async load() {
			this.isLoading = true;

			const share = await resourceService.getShareLink(this.resourceId);
			if (share) {
				this.code = share.code || '';
				this.hasPassword = !!share.hasPassword;
				// Backend returns 'Y-m-d H:i:s'; the date input only needs the day part.
				this.expirationDate = share.expirationDate ? share.expirationDate.substring(0, 10) : '';
			}

			this.password = '';
			this.isLoading = false;
		},
		async copyCode() {
			if (!this.code) {
				return;
			}

			try {
				await navigator.clipboard.writeText(this.shareUrl);
				await this.alertSuccess(this.translate('COM_EMUNDUS_RESOURCE_SHARE_CODE_COPIED'));
			} catch (e) {
				await this.alertError(this.translate('COM_EMUNDUS_RESOURCE_SHARE_CODE_COPY_ERROR'), e.message);
			}
		},
		async revoke() {
			if (this.isRevoking) {
				return;
			}

			this.isRevoking = true;
			try {
				const result = await resourceService.revokeShareLink(this.resourceId);
				if (result.status) {
					this.code = '';
					this.hasPassword = false;
					this.expirationDate = '';
					this.password = '';
					await this.alertSuccess(this.translate('COM_EMUNDUS_RESOURCE_SHARE_REVOKED'));
				} else {
					await this.alertError(this.translate('COM_EMUNDUS_RESOURCE_SHARE_REVOKE_ERROR'), result.msg);
				}
			} finally {
				this.isRevoking = false;
			}
		},
		async save() {
			// Nothing to persist yet: don't create an empty link just because the
			// shared "Enregistrer" button also covers this tab.
			if (!this.hasLink && this.expirationDate === '' && this.password === '') {
				return;
			}

			const result = await resourceService.saveShareLink(this.resourceId, {
				// Empty field keeps the current password (write-only, never returned).
				password: this.password !== '' ? this.password : null,
				expirationDate: this.expirationDate,
			});

			if (!result.status) {
				throw new Error(result.msg || this.translate('COM_EMUNDUS_RESOURCE_SHARE_SAVE_ERROR'));
			}

			if (result.data) {
				this.code = result.data.code || this.code;
				this.hasPassword = !!result.data.hasPassword;
				this.password = '';
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-w-full tw-flex-col tw-gap-6">
		<FormField :label="translate('COM_EMUNDUS_RESOURCE_SHARE_EXPIRATION')">
			<template #default="{ id }">
				<div class="tw-flex tw-items-center tw-gap-2">
					<input
						:id="id"
						v-model="expirationDate"
						type="date"
						class="tw-h-10 tw-min-w-0 tw-flex-1 tw-rounded-lg tw-border tw-border-neutral-100 tw-bg-white tw-px-3 tw-text-base tw-text-neutral-500"
					/>
				</div>
			</template>
		</FormField>

		<FormField
			:label="translate('COM_EMUNDUS_RESOURCE_SHARE_PASSWORD')"
			:help-text="hasPassword ? translate('COM_EMUNDUS_RESOURCE_SHARE_PASSWORD_SET') : ''"
		>
			<template #default="{ id }">
				<input
					:id="id"
					v-model="password"
					type="password"
					autocomplete="new-password"
					:placeholder="translate('COM_EMUNDUS_RESOURCE_SHARE_PASSWORD_PLACEHOLDER')"
					class="tw-h-10 tw-w-full tw-rounded-lg tw-border tw-border-neutral-100 tw-bg-white tw-px-3 tw-text-base tw-text-neutral-500"
				/>
			</template>
		</FormField>

		<FormField v-if="hasLink" :label="translate('COM_EMUNDUS_RESOURCE_SHARE_CODE')">
			<template #default="{ id }">
				<div class="tw-flex tw-items-center tw-gap-2">
					<input
						:id="id"
						:value="shareUrl"
						readonly
						class="tw-h-10 tw-min-w-0 tw-flex-1 tw-rounded-lg tw-border tw-border-neutral-100 tw-bg-white tw-px-3 tw-text-base tw-text-neutral-500"
					/>
					<Button variant="success" emphasis="lite" :label="translate('COM_EMUNDUS_COPY')" @click="copyCode">
						<template #leading>
							<Icon name="content_copy" />
						</template>
					</Button>
				</div>
			</template>
		</FormField>

		<p v-else class="tw-text-base tw-text-neutral-600">
			{{ translate('COM_EMUNDUS_RESOURCE_SHARE_CODE_HINT') }}
		</p>

		<div v-if="hasLink" class="tw-flex tw-justify-end">
			<Button
				variant="danger"
				emphasis="lite"
				:label="translate('COM_EMUNDUS_RESOURCE_SHARE_REVOKE')"
				:loading="isRevoking"
				@click="revoke"
			>
				<template #leading>
					<Icon name="link_off" />
				</template>
			</Button>
		</div>
	</div>
</template>

<style scoped></style>
