<template>
	<div class="tw-relative">
		<h2 v-if="displayTitle" class="tw-mb-6">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY') }}</h2>

		<Pagination
			v-if="history.length > 0"
			:dataLength="historyLength"
			:sticky="true"
			v-model:page="page"
			v-model:limit="limit"
		/>

		<div v-if="!loading" class="tw-relative tw-overflow-x-scroll">
			<table v-if="history.length > 0">
				<thead>
					<tr>
						<th v-if="columns.includes('itemId')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_ITEM_ID') }}</th>
						<th v-if="columns.includes('title')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_UPDATES') }}</th>
						<th v-if="columns.includes('message_language_key')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_TYPE') }}</th>
						<th v-if="columns.includes('log_date')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_LOG_DATE') }}</th>
						<th v-if="columns.includes('user_id')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_BY') }}</th>
						<th v-if="columns.includes('status')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS') }}</th>
						<th v-if="columns.includes('diff')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_DIFF') }}</th>
						<th v-if="columns.includes('retry')">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_RETRY') }}</th>
						<th v-for="column in moreData" :key="column">{{ column }}</th>
					</tr>
				</thead>

				<tbody>
					<tr v-for="data in history" :key="data.id">
						<td v-if="columns.includes('itemId')">
							<span> {{ data.message.id }} </span>
						</td>

						<td v-if="columns.includes('title')">
							<p>{{ translate(data.message.title) }}</p>

							<p v-if="data.message.entity && extension == 'com_emundus.microsoftdynamics'">
								<strong>{{ data.message.entity }}</strong>
							</p>

							<div
								v-if="
									data.message.new_data &&
									data.message.new_data.length > 0 &&
									extension == 'com_emundus.settings.web_security'
								"
							>
								<span v-for="(newData, index) in data.message.new_data" :key="index">
									<span v-if="index > 0">, </span>
									<span class="tw-text-green-700">{{ newData }}</span>
								</span>
							</div>
						</td>

						<td v-if="columns.includes('message_language_key')">
							{{ translate(data.message_language_key + '_TITLE') }}
						</td>

						<td v-if="columns.includes('log_date')">
							{{ formattedDate(data.log_date, 'L') + ' ' + formattedDate(data.log_date, 'LT') }}
						</td>

						<td v-if="columns.includes('user_id')">{{ data.logged_by }}</td>

						<td v-if="columns.includes('status')">
							<div class="tw-flex tw-items-center">
								<span class="material-symbols-outlined tw-mr-2" :class="colorClasses[data.message.status]">{{
									icon[data.message.status]
								}}</span>

								<p :class="colorClasses[data.message.status]">
									{{ translate(text[data.message.status]) }}

									<span
										v-if="
											(data.message.status == 'done' || data.message.status == 'cancelled') &&
											data.message.status_updated
										"
									>
										{{ formattedDate(data.message.status_updated, 'L') }}
									</span>
								</p>

								<span
									v-if="this.sysadmin && data.message.status === 'pending'"
									@click="updateHistoryStatus(data.id, 'done')"
									class="material-symbols-outlined tw-cursor-pointer"
								>
									edit
								</span>

								<span
									v-if="this.sysadmin && data.message.status === 'pending'"
									@click="updateHistoryStatus(data.id, 'cancelled')"
									class="material-symbols-outlined tw-cursor-pointer"
								>
									backspace
								</span>
							</div>

							<div>
								<button
									v-if="data.message.status === 'error' && data.message.message"
									type="button"
									class="tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-blue-500"
									@click="errorModalToOpen = data.id"
								>
									{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_SHOW_DETAILS') }}
								</button>
							</div>
						</td>
						<td v-if="columns.includes('diff')">
							<button
								type="button"
								class="tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-blue-500"
								@click="modalToOpen = data.id"
							>
								{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_DIFF_SHOW') }}
							</button>
						</td>

						<td v-if="columns.includes('retry')">
							<button
								v-if="data.message.status === 'error' && data.message.retry"
								type="button"
								class="tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-blue-500"
								@click="retry(data.id)"
							>
								<span class="material-symbols-outlined tw-text-blue-500">replay</span>

								{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_LAUNCH_RETRY') }}
							</button>
							<span v-else class="tw-block tw-w-full tw-text-center"> - </span>
						</td>

						<td v-for="column in moreData" :key="column">{{ data.message[column] }}</td>

						<modal
							v-if="errorModalToOpen === data.id"
							:name="'error-modal'"
							:class="'placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-overflow-x-hidden tw-rounded tw-p-4 tw-shadow-modal'"
							transition="nice-modal-fade"
							:width="'60%'"
							:delay="100"
							:adaptive="true"
							:clickToClose="false"
						>
							<div class="tw-sticky tw-top-0 tw-z-10 tw-border-b tw-border-neutral-300 tw-bg-white tw-pt-4">
								<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
									<h2>
										{{ translate('COM_EMUNDUS_GLOBAL_ERROR_DETAILS') }}
									</h2>
									<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="errorModalToOpen = null">
										<span class="material-symbols-outlined">close</span>
									</button>
								</div>
							</div>

							<div class="tw-mt-3">
								<p>{{ data.message.message }}</p>
							</div>
						</modal>

						<modal
							v-if="modalToOpen === data.id"
							:name="'datas-modal'"
							:class="'placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-overflow-x-hidden tw-rounded tw-p-4 tw-shadow-modal'"
							transition="nice-modal-fade"
							:width="'60%'"
							:delay="100"
							:adaptive="true"
							:clickToClose="false"
						>
							<div class="tw-sticky tw-top-0 tw-z-10 tw-border-b tw-border-neutral-300 tw-bg-white tw-pt-4">
								<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
									<h2>
										{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_DIFF') }}
									</h2>
									<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="modalToOpen = null">
										<span class="material-symbols-outlined">close</span>
									</button>
								</div>
							</div>

							<table
								v-if="
									columns.includes('diff') &&
									(data.message.old_data || data.message.new_data) &&
									(!Array.isArray(data.message.old_data) ||
										data.message.old_data.length > 0 ||
										!Array.isArray(data.message.new_data) ||
										data.message.new_data.length > 0)
								"
								class="tw-rounded !tw-border !tw-border-solid !tw-border-slate-100 tw-text-sm"
							>
								<thead>
									<tr>
										<th>{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_DIFF_COLUMN') }}</th>
										<th
											v-if="
												(!Array.isArray(data.message.old_data) && data.message.old_data) ||
												(Array.isArray(data.message.old_data) && data.message.old_data.length > 0)
											"
										>
											{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_DIFF_OLD_DATA') }}
										</th>
										<th v-if="data.message.new_data">{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_DIFF_NEW_DATA') }}</th>
									</tr>
								</thead>
								<tbody>
									<tr
										v-if="
											((!Array.isArray(data.message.old_data) && data.message.old_data) ||
												(Array.isArray(data.message.old_data) && data.message.old_data.length > 0)) &&
											data.message.new_data
										"
										v-for="(value, key) in data.message.old_data"
										:key="'old_data_' + key"
									>
										<td>{{ key }}</td>
										<td>{{ value }}</td>
										<td>{{ data.message.new_data_json[key] }}</td>
									</tr>
									<tr
										v-else-if="(!data.message.old_data || data.message.old_data.length == 0) && data.message.new_data"
										v-for="(value, key) in data.message.new_data_json"
										:key="'new_data_' + key"
									>
										<td>{{ key }}</td>
										<td>{{ value }}</td>
									</tr>
								</tbody>
							</table>
						</modal>
					</tr>
				</tbody>
			</table>

			<div v-else>
				<p>{{ translate('COM_EMUNDUS_GLOBAL_HISTORY_NO_HISTORY') }}</p>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
/* SERVICES */
import settingsService from '@/services/settings';

/* MIXINS */
import mixin from '@/mixins/mixin.js';

/* STORE */
import { useGlobalStore } from '@/stores/global.js';
import Pagination from '@/components/Utils/Pagination.vue';
import Modal from '@/components/Modal.vue';

export default {
	name: 'History',
	components: { Modal, Pagination },
	props: {
		extension: {
			type: String,
			required: true,
		},
		itemId: {
			type: Number,
			default: 0,
		},
		columns: {
			type: Array,
			default: () => [
				// Modification(s)
				'title',
				// Type
				'message_language_key',
				// Date
				'log_date',
				// By
				'user_id',
				// Status
				//'status',
				'diff',
			],
		},
		moreData: {
			type: Array,
			default: () => [],
		},
		displayTitle: {
			type: Boolean,
			default: false,
		},
	},
	mixins: [mixin],
	data() {
		return {
			loading: true,
			modalToOpen: null,
			errorModalToOpen: null,

			colorClasses: {
				done: 'tw-text-main-500',
				success: 'tw-text-main-500',
				pending: 'tw-text-orange-500',
				cancelled: 'tw-text-red-500',
				error: 'tw-text-red-500',
			},
			icon: {
				done: 'check_circle',
				success: 'check_circle',
				pending: 'rule_settings',
				cancelled: 'cancel',
				error: 'cancel',
			},
			text: {
				done: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_DONE'),
				pending: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_PENDING'),
				cancelled: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_CANCELLED'),
				success: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_SUCCESS'),
				error: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_ERROR'),
			},

			history: [],
			historyLength: 0,
			page: 1,
			limit: 10,
		};
	},
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},
	created() {
		this.fetchHistory();
	},
	methods: {
		fetchHistory() {
			this.loading = true;

			settingsService.getHistory(this.extension, false, this.page, this.limit, this.itemId).then((response) => {
				this.historyLength = parseInt(response.length);

				response.data.forEach((data) => {
					//data.log_date = new Date(data.log_date).toLocaleString();
					data.message = JSON.parse(data.message);
					if (data.message.old_data) {
						if (typeof data.message.new_data === 'string') {
							data.message.old_data = JSON.parse(data.message.old_data);
						}
					}
					if (data.message.new_data) {
						if (typeof data.message.new_data === 'string') {
							data.message.new_data = JSON.parse(data.message.new_data);
						}
						data.message.new_data_json = JSON.parse(JSON.stringify(data.message.new_data));
					}

					// Convert data.message.new_data object to array
					if (data.message.new_data) {
						data.message.new_data = Object.values(data.message.new_data);
					}
				});

				this.history = response.data;
				this.loading = false;
			});
		},

		updateHistoryStatus(id, status) {
			if (this.sysadmin) {
				Swal.fire({
					title: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_TITLE'),
					text: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_TEXT'),
					showCancelButton: true,
					reverseButtons: true,
					confirmButtonText: this.translate('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_YES'),
					cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
					customClass: {
						title: 'em-swal-title',
						cancelButton: 'em-swal-cancel-button',
						confirmButton: 'em-swal-confirm-button',
					},
				}).then((result) => {
					if (result.isConfirmed) {
						settingsService.updateHistoryStatus(id, status).then(() => {
							this.fetchHistory();
						});
					}
				});
			}
		},
		retry(actionLogId) {
			settingsService.historyRetryEvent(actionLogId).then(() => {
				this.fetchHistory();
			});
		},
	},
	computed: {
		sysadmin: function () {
			return parseInt(this.globalStore.hasSysadminAccess);
		},
	},
	watch: {
		page: function () {
			this.fetchHistory();
		},
		limit: function () {
			this.fetchHistory();
		},
	},
};
</script>

<style scoped>
table {
	border: unset;
}

table thead th {
	background: transparent;
	padding: 18px 12px;
}

table thead tr {
	border-bottom: solid 1px var(--neutral-400);
}

table tbody tr:not(:last-child) {
	border-bottom: solid 1px var(--neutral-400);
}

table tbody tr td {
	padding: 18px 12px;
}

.placement-center {
	position: fixed;
	left: 50%;
	transform: translate(-50%, -50%);
	top: 50%;
}
</style>
