<template>
	<div class="em-settings-menu">
		<div v-if="!loading">
			<div class="tw-relative tw-mb-8 tw-flex tw-h-56 tw-w-80 tw-rounded tw-bg-white tw-shadow-md" name="tilebutton">
				<button
					type="button"
					@click="redirect(this.$props.link)"
					class="tw-absolute tw-left-1/2 tw-top-1/2 tw-flex tw--translate-x-1/2 tw--translate-y-1/2 tw-transform tw-flex-col tw-items-center tw-justify-center tw-rounded"
				>
					<div
						class="tw-flex tw-items-center tw-justify-center tw-rounded"
						:style="{
							'background-color': this.$props.color,
							width: '16em',
							height: '10em',
						}"
					>
						<i class="material-symbols-outlined em-color-white tw-scale-[4]">{{ this.$props.icon }}</i>
					</div>
					<div class="tw-flex tw-items-center tw-justify-center tw-font-bold">
						{{ translate(this.$props.title) }}
					</div>
				</button>
			</div>
		</div>
		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import mixin from '@/mixins/mixin';

export default {
	name: 'Tile',

	components: {
		//Parameter,
	},

	props: {
		name: {
			default: null,
		},
		link: {
			default: null,
		},
		icon: {
			default: null,
		},
		title: {
			default: null,
		},
		color: {
			default: null,
		},
	},

	mixins: [mixin],
	data() {
		return {
			defaultLang: null,
			availableLanguages: [],
			subSection: [],
			Initname: this.$props.name,
			lang: null,
			loading: false,
			dynamicComponent: 0,
			updated: false,
			subSectionNotif: this.$props.notify,
			form: {
				published: this.$props.published,
				content: '',
			},
		};
	},

	created() {
		this.loading = true;
		this.loading = false;
	},

	methods: {
		handleNeedSaving(needSaving, article) {
			this.$store.commit('settings/setNeedSaving', needSaving);
			this.$store.commit('settings/setArticle', article);
			this.$emit('NeedSaving', needSaving, article);
		},

		saveMethod() {
			let vue_component = this.$refs['component_' + this.$props.name];
			if (vue_component && typeof vue_component.saveContent === 'function') {
				vue_component.saveContent();
			}
		},
	},
	watch: {},
};
</script>
<style scoped></style>
