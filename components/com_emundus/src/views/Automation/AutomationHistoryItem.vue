<script>
export default {
	name: 'AutomationHistoryItem',
	props: {
		item: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			message: {},
			rows: [],
			successes: [],
			failures: [],
		};
	},
	mounted() {
		this.message = this.item && this.item.message ? JSON.parse(this.item.message) : {};

		this.rows = [
			{ label: 'COM_EMUNDUS_AUTOMATION_PROCESS_ID', value: this.item.id },
			{ label: 'COM_EMUNDUS_AUTOMATION', value: this.message.automation_entity?.name || '' },
			{ label: 'COM_EMUNDUS_AUTOMATION_PROCESS_NB_FILES_CONTEXT', value: this.message.nb_files || 0 },
			{ label: 'COM_EMUNDUS_AUTOMATION_PROCESS_NB_FILES_PROCESSED', value: this.message.nb_files_processed || 0 },
			{
				label: 'COM_EMUNDUS_AUTOMATION_PROCESS_NB_SUCCESSFULL_ACTIONS',
				value: this.message.successful_actions ? this.message.successful_actions.length : 0,
			},
			{
				label: 'COM_EMUNDUS_AUTOMATION_PROCESS_NB_FAILED_ACTIONS',
				value: this.message.failed_actions ? this.message.failed_actions.length : 0,
			},
			{
				label: 'COM_EMUNDUS_AUTOMATION_FILES',
				value: this.message.context && this.message.context.files ? this.message.context.files.join(', ') : '',
			},
		];

		if (this.message.successful_actions && this.message.successful_actions.length > 0) {
			this.message.successful_actions.forEach((success) => {
				this.successes.push({
					label: this.translate(success.label),
					value: success.context.file ? success.context.file : success.context.user ? success.context.user : '',
				});
			});
		}

		if (this.message.failed_actions && this.message.failed_actions.length > 0) {
			this.message.failed_actions.forEach((failure) => {
				this.failures.push({
					label: this.translate(failure.label),
					value: failure.context.file ? failure.context.file : failure.context.user ? failure.context.user : '',
				});
			});
		}
	},
};
</script>

<template>
	<div id="automation-history-item" class="tw-flex tw-flex-col tw-gap-2">
		<div v-for="row in rows" :key="row.label">
			<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
				<div>
					<strong>{{ translate(row.label) }}</strong>
				</div>
				<div>{{ row.value }}</div>
			</div>
			<hr />
		</div>

		<div v-if="successes.length > 0" class="tw-mb-4">
			<div>
				<span class="tw-underline"
					>{{ translate('COM_EMUNDUS_AUTOMATION_SUCCESSFUL_ACTIONS') + ' : ' }}
					{{ message.nb_successful_actions }}</span
				>
			</div>
			<table class="tw-mt-4">
				<thead>
					<tr>
						<th>{{ translate('COM_EMUNDUS_AUTOMATION_ACTION') }}</th>
						<th>{{ translate('COM_EMUNDUS_AUTOMATION_TARGET') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="success in successes" :key="success.label">
						<td>{{ success.label }}</td>
						<td>{{ success.value }}</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div v-if="failures.length > 0" class="tw-mb-4">
			<div>
				<span class="tw-underline"
					>{{ translate('COM_EMUNDUS_AUTOMATION_FAILED_ACTIONS') + ' : ' }} {{ message.nb_failed_actions }}</span
				>
			</div>
			<table class="tw-mt-4">
				<thead>
					<tr>
						<th>{{ translate('COM_EMUNDUS_AUTOMATION_ACTION') }}</th>
						<th>{{ translate('COM_EMUNDUS_AUTOMATION_TARGET') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="failure in failures" :key="failure.label">
						<td>{{ failure.label }}</td>
						<td>{{ failure.value }}</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<style scoped></style>
