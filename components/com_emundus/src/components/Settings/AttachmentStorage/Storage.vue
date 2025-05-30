<template>
	<div>
		<h2>{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_STORAGE') }}</h2>

		<table>
			<thead>
				<tr>
					<th>
						{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_DOCTYPE') }}
					</th>
					<th>
						{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_STATUS') }}
					</th>
					<th>
						{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_STORAGE_TYPE') }}
					</th>
					<th>
						{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_SYNCHRO') }}
					</th>
				</tr>
				<tr v-for="document in documents">
					<td @click="openAttachmentParameters(document)">
						{{ document.value }}
					</td>
					<td></td>
					<td>
						<select
							class="em-clear-dropdown tw-mr-2"
							v-model="document.sync"
							@change="updateSync(document.id, document.sync)"
						>
							<option :value="type.value" v-for="type in syncTypes">
								{{ translate(type.label) }}
							</option>
						</select>
					</td>
					<td>
						<select
							class="em-clear-dropdown tw-mr-2"
							v-if="document.sync != 0"
							v-model="document.sync_method"
							@change="updateSyncMethod(document.id, document.sync_method)"
						>
							<option :value="'write'" selected>
								{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_SYNC_WRITE') }}
							</option>
							<option :value="'read'">
								{{ translate('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_SYNC_READ') }}
							</option>
						</select>
					</td>
				</tr>
			</thead>
		</table>

		<modal
			:name="'attachmentParameters'"
			transition="fade"
			:delay="100"
			:adaptive="true"
			id="modal-attachment-parameters"
			class="em-w-25 tw-h-full"
		>
			<AttachmentParameters :attachment="selectedDocument"></AttachmentParameters>
		</modal>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import syncService from '@/services/sync.js';
import syncs from '../../../data/ged/syncType';
import mixin from '@/mixins/mixin.js';
import AttachmentParameters from './AttachmentParameters.vue';

export default {
	name: 'Storage',
	components: {
		AttachmentParameters,
	},
	mixins: [mixin],
	data() {
		return {
			loading: false,
			documents: [],
			syncTypes: [],
			selectedDocument: {},
		};
	},
	mounted() {
		this.syncTypes = syncs['sync_type'];
	},
	created() {
		syncService.getDocuments().then((response) => {
			this.documents = response.data.data;
		});
	},
	methods: {
		updateSync(did, sync) {
			this.$emit('updateSaving', true);
			syncService.updateSync(did, sync).then(() => {
				this.$emit('updateSaving', false);
				this.$emit('updateLastSaving', this.formattedDate('', 'LT'));
			});
		},
		updateSyncMethod(did, sync_method) {
			this.$emit('updateSaving', true);
			syncService.updateSyncMethod(did, sync_method).then(() => {
				this.$emit('updateSaving', false);
				this.$emit('updateLastSaving', this.formattedDate('', 'LT'));
			});
		},
		openAttachmentParameters(document) {
			this.selectedDocument = document;
			this.$modal.show('attachmentParameters');
		},
	},
};
</script>

<style scoped>
table {
	border: unset;
}

th {
	background: unset;
	border-bottom: 2px solid #ddd;
	border-top: unset;
	border-right: unset;
	border-left: unset;
}

td {
	border-bottom: 1px solid #ddd;
	border-top: unset;
	border-right: unset;
	border-left: unset;
	padding-bottom: 16px;
	padding-top: 16px;
}

.em-clear-dropdown {
	border: unset;
	height: auto;
}

.em-clear-dropdown:focus {
	outline: unset;
	background: #e3e5e8;
}
</style>
