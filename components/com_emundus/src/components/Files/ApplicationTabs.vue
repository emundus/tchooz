<template>
	<div>
		<Tabs
			:tabs="accessibleTabs"
			:classes="'tw-flex tw-items-center tw-gap-2 tw-ml-7'"
			@changeTabActive="selected = $event"
		></Tabs>

		<div class="em-border-top-neutral-300 tw-mb-4 tw-ml-4 tw-mr-4 tw-rounded-lg tw-p-2 tw-shadow">
			<div v-if="selected === 'application'" v-html="applicationform"></div>
			<Attachments
				v-if="selected === 'attachments'"
				:fnum="file.fnum"
				:user="user"
				:columns="['name', 'date', 'category', 'status']"
				:displayEdit="false"
				:centerPreview="false"
			/>
			<Comments v-if="selected === 'comments'" :fnum="file.fnum" :user="user" :access="access['10']" />
			<IframeTab v-if="selectedTab && selectedTab.url" :key="selectedTab.name" :url="selectedTab.url" :fnum="file.fnum">
			</IframeTab>
		</div>
	</div>
</template>

<script>
import Attachments from '@/views/Attachments.vue';
import Comments from '@/components/Files/Comments.vue';
import EvaluationForm from '@/components/Files/EvaluationForm.vue';
import IframeTab from '@/components/Files/IframeTab.vue';
import filesService from '@/services/files.js';
import Tabs from '@/components/utils/Tabs.vue';

export default {
	name: 'ApplicationTabs',
	components: { EvaluationForm, Attachments, Comments, IframeTab, Tabs },
	props: {
		tabs: {
			type: Array,
			default: () => [
				{
					label: 'COM_EMUNDUS_FILES_APPLICANT_FILE',
					name: 'application',
					access: '1',
				},
				{
					label: 'COM_EMUNDUS_FILES_ATTACHMENTS',
					name: 'attachments',
					access: '4',
				},
				{
					label: 'COM_EMUNDUS_FILES_COMMENTS',
					name: 'comments',
					access: '10',
				},
			],
		},
		access: {
			type: Object,
			required: true,
		},
		file: {
			type: Object,
			required: true,
		},
		user: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			selected: 'application',
			applicationform: '',
		};
	},
	mounted() {
		if (this.tabs.some((tab) => tab.name === 'application')) {
			this.getApplicationForm();
		}
	},
	methods: {
		getApplicationForm() {
			filesService.getApplicationForm(this.file.fnum).then((html) => {
				if (html) {
					this.applicationform = html;
				}
			});
		},
	},
	computed: {
		selectedTab() {
			return this.tabs.find((tab) => tab.name === this.selected);
		},
		urlTabs() {
			return this.tabs.filter((tab) => {
				return tab.url;
			});
		},
		accessibleTabs() {
			return this.tabs
				.filter((tab) => {
					return this.access && this.access[tab.access] && this.access[tab.access].r;
				})
				.map((tab) => {
					return {
						id: tab.name,
						name: tab.label,
						disabled: false,
						displayed: true,
						active: tab.name === this.selected,
						icon: '',
					};
				});
		},
	},
};
</script>

<style lang="scss" scoped>
#application-tabs > div {
	width: 100%;
	overflow: scroll;
}
</style>
