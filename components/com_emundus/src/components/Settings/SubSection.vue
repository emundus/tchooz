<template>
	<div class="em-settings-menu">
		<div class="tw-w-full" v-if="!loading">
			<div v-if="$props.props.published">
				<div class="tw-flex tw-items-center tw-pb-8 tw-cursor-pointer" @click="handleToogleContent">
					<span class="tw-text-xl tw-font-bold">{{ translate(name) }}</span>
					<i
						class="material-symbols-outlined scale-150"
						:id="'SubSectionArrow' + $props.name"
						name="SubSectionArrows"
						style="transform-origin: unset"
						>expand_more</i
					>
					<div
						:key="keyNotif"
						v-if="subSectionNotif === true"
						class="tw-inline-flex tw-items-center tw-justify-center tw-w-6 tw-h-6 tw-bg-red-500 tw-box-border-2 tw-border-white tw-rounded-full -top-2 -end-2"
					></div>
				</div>
				<div :id="'SubSection-' + $props.name" name="SubSectionContent" style="display: none" class="flex flex-col">
					<div>
						<div class="flex flex-col">
							<component
								:is="$props.component"
								v-bind="$props.props"
								:ref="'component_' + $props.name"
								@needSaving="handleNeedSaving"
								@needNotify="updateNotif"
							>
							</component>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import mixin from '@/mixins/mixin';
import Multiselect from 'vue-multiselect';
import Parameter from '@/components/Utils/Parameter.vue';
import EditArticle from '@/components/Settings/Content/EditArticle.vue';

export default {
	name: 'SubSection',

	components: {
		Parameter,
		Multiselect,
		EditArticle,
	},

	props: {
		name: {
			default: null,
		},
		component: {
			type: String,
		},
		props: {
			type: Object,
		},
		json_source: {
			type: String,
			required: true,
		},
		notify: {
			type: Boolean,
			required: false,
		},
		index: {
			type: Number,
			required: false,
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
			keyNotif: 0,
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
		toggleVisibilityContent() {
			let SubSectionArrow = document.getElementById('SubSectionArrow' + this.$props.name);
			let SubSectionContent = document.getElementById('SubSection-' + this.$props.name);

			if (SubSectionContent.style.display === 'none') {
				SubSectionContent.style.display = 'block';
				SubSectionArrow.style.transform = 'rotate(180deg)';
			} else {
				SubSectionContent.style.display = 'none';
				SubSectionArrow.style.transform = 'rotate(0deg)';
			}
		},
		handleToogleContent() {
			this.toggleVisibilityContent();
		},
		handleNeedSaving(needSaving, article) {
			this.$emit('needSaving', needSaving, article);
		},
		updateNotif(needNotify) {
			this.subSectionNotif = needNotify;
			this.keyNotif++;
			this.$emit('updateNotif', this.$props.index, needNotify);
		},
		saveMethod(notif) {
			this.$emit('updateNotif', !notif);
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
