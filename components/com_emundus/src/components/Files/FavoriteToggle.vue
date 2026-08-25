<template>
	<span
		v-if="fnum"
		class="em-favorite-toggle tw-flex tw-cursor-pointer tw-items-center tw-rounded-md tw-p-1 tw-transition-colors hover:tw-bg-white/30"
		role="button"
		tabindex="0"
		:title="label"
		:aria-label="label"
		:aria-pressed="favorite ? 'true' : 'false'"
		@click="toggle"
		@keydown.enter.prevent="toggle"
		@keydown.space.prevent="toggle"
	>
		<!-- material-icons, not the design-system Icon: that one renders Material Symbols Outlined,
		     where `star` is an empty outline and `star_border` does not exist. The filled state is
		     the whole point here, and this markup matches EmundusHelperFiles::createFavoriteToggle(). -->
		<span class="material-icons" style="font-size: 18px" :class="favorite ? 'tw-text-white' : 'tw-text-white/60'">{{
			favorite ? 'star' : 'star_border'
		}}</span>
	</span>
</template>

<script>
import fileService from '@/services/file.js';

export default {
	name: 'FavoriteToggle',
	props: {
		fnum: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			favorite: false,
			pending: false,
		};
	},
	computed: {
		label() {
			return this.translate(this.favorite ? 'COM_EMUNDUS_FAVORITES_REMOVE' : 'COM_EMUNDUS_FAVORITES_ADD');
		},
	},
	watch: {
		fnum: {
			immediate: true,
			handler() {
				this.load();
			},
		},
	},
	methods: {
		async load() {
			this.favorite = await fileService.isFavorite(this.fnum);
		},
		async toggle() {
			if (this.pending) {
				return;
			}
			this.pending = true;

			try {
				const response = await fileService.toggleFavorite(this.fnum);

				// Only follow the server: the icon must never claim a change that was refused.
				if (response.status) {
					this.favorite = response.data.favorite;
				} else {
					this.alertError(this.translate('COM_EMUNDUS_FAVORITES_TOGGLE_FAILED'));
				}
			} catch (e) {
				this.alertError(this.translate('COM_EMUNDUS_FAVORITES_TOGGLE_FAILED'));
			} finally {
				this.pending = false;
			}
		},
	},
};
</script>
