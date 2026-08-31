<script>
import { Button, Icon } from '@emundus/ui';

import resourceService from '@/services/resource.js';
import { useResourceStore } from '@/stores/resource.js';
import alerts from '@/mixins/alerts.js';
import ModalHeader from '@/components/Utils/Modal/Header.vue';

const SHADOW_DOM_STYLES = ['sheet', 'presentation', 'word'];
const MSG_TIMEOUT = 10000;

export default {
	name: 'ResourcePreview',
	components: { ModalHeader, Button, Icon },
	mixins: [alerts],
	props: {
		item: {
			type: Object,
			required: true,
		},
		// Files of the current level (display order), used to browse to the previous/next document.
		siblings: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['close', 'update-items'],
	data() {
		return {
			// The document currently shown; navigation swaps it without closing the modal.
			activeItem: this.item,
			preview: '',
			style: '',
			overflowX: false,
			overflowY: false,
			needShadowDOM: false,
			msg: '',
			openMsg: false,
			isLoading: true,
			hasError: false,
			isDownloading: false,
			msgTimer: null,
			currentName: '',
			isEditingName: false,
			nameDraft: '',
			isRenaming: false,
		};
	},
	setup() {
		return {
			resourceStore: useResourceStore(),
		};
	},
	created() {
		this.currentName = this.title;
	},
	computed: {
		// List rows expose a prefixed id ("file-42"); the backend needs the numeric part.
		resourceId() {
			return parseInt(String(this.activeItem.id).split('-').pop(), 10);
		},
		title() {
			return this.activeItem.name || this.activeItem.label?.fr || '';
		},
		currentIndex() {
			return this.siblings.findIndex((sibling) => sibling.id === this.activeItem.id);
		},
		hasPrevious() {
			return this.currentIndex > 0;
		},
		hasNext() {
			return this.currentIndex !== -1 && this.currentIndex < this.siblings.length - 1;
		},
	},
	mounted() {
		if (typeof this.$refs.preview?.attachShadow === 'function') {
			this.$refs.preview.attachShadow({ mode: 'open' });
		}

		this.loadPreview();

		window.addEventListener('keydown', this.onKeydown);
	},
	beforeUnmount() {
		if (this.msgTimer) {
			clearTimeout(this.msgTimer);
			this.msgTimer = null;
		}

		window.removeEventListener('keydown', this.onKeydown);
	},
	methods: {
		onKeydown(event) {
			// Let the user type freely while renaming.
			if (this.isEditingName) {
				return;
			}

			if (event.key === 'ArrowLeft') {
				this.goToPrevious();
			} else if (event.key === 'ArrowRight') {
				this.goToNext();
			}
		},
		goToPrevious() {
			if (this.hasPrevious) {
				this.goToSibling(this.currentIndex - 1);
			}
		},
		goToNext() {
			if (this.hasNext) {
				this.goToSibling(this.currentIndex + 1);
			}
		},
		goToSibling(index) {
			if (this.isLoading) {
				return;
			}

			// Cancel any pending "message" timer of the document we are leaving.
			if (this.msgTimer) {
				clearTimeout(this.msgTimer);
				this.msgTimer = null;
			}

			this.activeItem = this.siblings[index];
			this.currentName = this.title;
			this.isEditingName = false;
			this.openMsg = false;

			this.loadPreview();
		},
		async loadPreview() {
			this.hasError = false;

			// Serve an already-loaded preview from the store; only fetch (and cache) on a miss.
			let data = this.resourceStore.getPreview(this.resourceId);

			if (data === null) {
				this.isLoading = true;
				data = await resourceService.getPreview(this.resourceId);
				this.isLoading = false;

				// Cache only a usable preview so a transient failure can still be retried.
				if (data.status || data.content) {
					this.resourceStore.setPreview(this.resourceId, data);
				}
			} else {
				this.isLoading = false;
			}

			this.preview = data.content || '';
			this.style = data.style || '';
			this.overflowX = !!data.overflowX;
			this.overflowY = !!data.overflowY;
			this.msg = data.msg || '';
			this.needShadowDOM = SHADOW_DOM_STYLES.includes(this.style);

			// A failed preview with no fallback content is a hard error; otherwise we still render.
			if (!data.status && !this.preview) {
				this.hasError = true;
				return;
			}

			if (this.msg) {
				this.openMsg = true;
				this.msgTimer = setTimeout(() => {
					this.openMsg = false;
					this.msgTimer = null;
				}, MSG_TIMEOUT);
			}

			this.renderPreview();
		},
		renderPreview() {
			const shadowRoot = this.$refs.preview?.shadowRoot;
			if (!shadowRoot) {
				return;
			}

			if (!this.needShadowDOM) {
				shadowRoot.innerHTML = '';
				return;
			}

			shadowRoot.innerHTML = this.preview;

			if (this.style === 'sheet') {
				this.addSheetStyles();
			} else if (this.style === 'presentation') {
				this.addPresentationStyles();
			} else if (this.style === 'word') {
				this.addWordStyles();
			}
		},
		addSheetStyles() {
			const shadowRoot = this.$refs.preview.shadowRoot;
			const pages = shadowRoot.querySelectorAll('div');
			pages.forEach((div, key) => {
				div.style.width = 'fit-content';
				div.style.margin = '20px auto';
				div.style.boxShadow = '0px 0px 10px rgba(0, 0, 0, 0.1)';

				if (key > 0) {
					div.style.display = 'none';
				}
			});

			const navigation = shadowRoot.querySelector('.navigation');
			if (navigation) {
				navigation.style.display = 'flex';
				navigation.style.flexDirection = 'row';
				navigation.style.justifyContent = 'flex-start';
				navigation.style.alignItems = 'center';

				navigation.querySelectorAll('li').forEach((li, liKey) => {
					li.style.listStyleType = 'none';
					li.style.margin = '0 10px';

					li.addEventListener('click', () => {
						pages.forEach((div, divKey) => {
							div.style.display = divKey === liKey ? 'block' : 'none';
						});
					});
				});
			}
		},
		addPresentationStyles() {
			const slides = this.$refs.preview.shadowRoot.querySelectorAll('.slide');
			slides.forEach((slide) => {
				slide.style.padding = '16px';
				slide.style.margin = '20px';
				slide.style.width = 'calc(100% - 72px)';
				slide.style.boxShadow = '0px 0px 10px rgba(0, 0, 0, 0.1)';
				slide.style.borderRadius = '8px';
				slide.style.backgroundColor = 'white';
			});
		},
		addWordStyles() {
			const wrapper = this.$refs.preview.shadowRoot.querySelector('.wrapper');
			if (wrapper) {
				wrapper.style.boxShadow = '0px 0px 10px rgba(0, 0, 0, 0.1)';
				wrapper.style.backgroundColor = 'white';
				wrapper.style.padding = '20px';
				wrapper.style.margin = '16px';
				wrapper.style.overflow = 'hidden';
			}
		},
		async onDownload() {
			if (this.isDownloading) {
				return;
			}

			this.isDownloading = true;

			const url = await resourceService.getDownloadUrl(this.resourceId);
			if (url) {
				window.open(url, '_blank');
			}

			this.isDownloading = false;
		},
		startRename() {
			if (!this.activeItem.can_update) {
				return;
			}

			this.nameDraft = this.currentName;
			this.isEditingName = true;
		},
		cancelRename() {
			this.isEditingName = false;
		},
		async saveRename() {
			const name = this.nameDraft.trim();

			// No-op on an empty or unchanged name; the backend still enforces both.
			if (name === '' || name === this.currentName) {
				this.isEditingName = false;
				return;
			}

			this.isRenaming = true;
			// activeItem.id is the list reference ("file-3") so the backend resolves the type + access check.
			const result = await resourceService.rename(this.activeItem.id, name);
			this.isRenaming = false;

			if (result.status) {
				this.currentName = result.data?.name ?? name;
				this.isEditingName = false;
				this.$emit('update-items');
			} else {
				this.alertError('COM_EMUNDUS_ERROR', result.msg || 'COM_EMUNDUS_UNKNOWN_ERROR');
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-h-full tw-flex-col tw-bg-neutral-100">
		<ModalHeader class="tw-px-6 tw-py-4" @close="$emit('close')">
			<template #leading>
				<div class="tw-flex tw-items-center tw-gap-2">
					<template v-if="!isEditingName">
						<h2>{{ currentName }}</h2>
						<Button
							v-if="activeItem.can_update"
							variant="neutral"
							emphasis="ghost"
							class="tw-w-fit"
							:title="translate('COM_EMUNDUS_RESOURCES_ACTION_RENAME')"
							@click.prevent="startRename"
						>
							<template #leading>
								<Icon name="edit" />
							</template>
						</Button>
					</template>
					<template v-else>
						<input
							v-model="nameDraft"
							type="text"
							maxlength="255"
							class="tw-rounded tw-border tw-border-neutral-300 tw-px-2 tw-py-1"
							@keyup.enter="saveRename"
							@keyup.esc="cancelRename"
						/>
						<Button
							variant="primary"
							:loading="isRenaming"
							:title="translate('COM_EMUNDUS_ONBOARD_OK')"
							@click.prevent="saveRename"
						>
							<template #leading>
								<Icon name="check" />
							</template>
						</Button>
						<Button
							variant="neutral"
							emphasis="ghost"
							class="tw-w-fit"
							:title="translate('COM_EMUNDUS_ONBOARD_CANCEL')"
							@click.prevent="cancelRename"
						>
							<template #leading>
								<Icon name="close" />
							</template>
						</Button>
					</template>
				</div>
			</template>
			<template #trailing>
				<div class="tw-flex tw-items-center tw-justify-end tw-gap-3">
					<Button
						variant="primary"
						:loading="isDownloading"
						:title="translate('COM_EMUNDUS_RESOURCES_PREVIEW_DOWNLOAD')"
						@click="onDownload"
					>
						<template #leading>
							<Icon name="download" />
						</template>
					</Button>
					<Button
						@click.prevent="$emit('close')"
						variant="neutral"
						emphasis="ghost"
						class="tw-w-fit"
						:title="translate('COM_EMUNDUS_CLOSE')"
					>
						<template #leading>
							<Icon name="close" />
						</template>
					</Button>
				</div>
			</template>
		</ModalHeader>

		<div id="em-resource-preview" class="tw-relative tw-min-h-0 tw-flex-1 tw-overflow-hidden tw-border-t">
			<div
				ref="preview"
				class="resource-preview"
				:class="{
					'tw-overflow-x-auto': overflowX,
					'tw-overflow-y-auto': overflowY,
					'tw-hidden': !needShadowDOM || isLoading || hasError,
				}"
			></div>
			<div v-if="!needShadowDOM && !isLoading && !hasError" v-html="preview" class="resource-preview"></div>

			<p v-if="isLoading" class="tw-flex tw-h-full tw-items-center tw-justify-center tw-text-neutral-400">
				{{ translate('COM_EMUNDUS_RESOURCES_PREVIEW_LOADING') }}
			</p>
			<p v-else-if="hasError" class="tw-flex tw-h-full tw-items-center tw-justify-center tw-text-neutral-400">
				{{ translate('COM_EMUNDUS_RESOURCES_PREVIEW_ERROR') }}
			</p>

			<div id="msg" :class="{ active: msg && openMsg }">
				<p>{{ msg }}</p>
			</div>

			<nav
				v-if="siblings.length > 1"
				class="tw-absolute tw-bottom-6 tw-left-1/2 tw-flex -tw-translate-x-1/2 tw-items-center tw-gap-2 tw-rounded-coordinator tw-bg-white tw-px-3 tw-py-2 tw-shadow-md"
			>
				<Button
					variant="neutral"
					emphasis="ghost"
					class="tw-w-fit"
					:disabled="!hasPrevious || isLoading"
					:title="translate('COM_EMUNDUS_RESOURCES_PREVIEW_PREVIOUS')"
					@click.prevent="goToPrevious"
				>
					<template #leading>
						<Icon name="chevron_left" />
					</template>
				</Button>
				<span
					class="tw-min-w-[2rem] tw-rounded tw-border tw-border-neutral-300 tw-px-2 tw-py-1 tw-text-center tw-text-sm tw-font-medium"
				>
					{{ currentIndex + 1 }}
				</span>
				<span class="tw-whitespace-nowrap tw-text-sm tw-text-neutral-500">/{{ siblings.length }}</span>
				<Button
					variant="neutral"
					emphasis="ghost"
					class="tw-w-fit"
					:disabled="!hasNext || isLoading"
					:title="translate('COM_EMUNDUS_RESOURCES_PREVIEW_NEXT')"
					@click.prevent="goToNext"
				>
					<template #leading>
						<Icon name="chevron_right" />
					</template>
				</Button>
			</nav>
		</div>
	</div>
</template>

<style lang="scss" scoped>
#em-resource-preview {
	.resource-preview {
		height: 100%;
		width: 100%;
		overflow: auto;
		background-color: #e4e4ed;
	}

	#msg {
		position: absolute;
		top: 20px;
		left: 8px;
		padding: 16px;
		width: calc(100% - 26px);
		background-color: #fff8e5;
		color: #b95000;
		opacity: 0;
		z-index: -1;
		transition: all 0.3s;

		&.active {
			opacity: 1;
			z-index: 2;
		}
	}
}
</style>
