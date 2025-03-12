<template>
	<div id="gantt-view">
		<div id="gantt-options"></div>

		<div id="gantt-head" class="tw-flex tw-flex-row">
			<span v-for="value in dateRange" :key="value">{{ value }}</span>
		</div>
		<div id="gantt-rows"></div>
	</div>
</template>

<script>
export default {
	name: 'Gantt',
	props: {
		defaultDisplay: {
			type: String,
			default: 'year',
		},
		defaultStartDate: {
			type: String,
			default: new Date(),
		},
		defaultEndDate: {
			type: String,
			default: new Date().setDate(new Date().getDate() + 365),
		},
		language: {
			type: String,
			default: 'fr-FR',
		},
		periods: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			display: this.defaultDisplay,
			displayValues: ['year', 'month', 'week', 'day'],
			dateRange: [],
		};
	},
	mounted() {
		this.setDateRange(this.defaultStartDate, this.defaultEndDate);
	},
	methods: {
		changeDisplay(value) {
			if (this.displayValues.includes(value)) {
				this.display = value;
			}
		},
		setDateRange(startDate, endDate) {
			if (startDate && endDate) {
				switch (this.display) {
					case 'year':
						// get all month between start and end date
						const start = new Date(startDate);
						const end = new Date(endDate);
						let date = new Date(start);
						this.dateRange = [];

						while (date <= end) {
							const formattedString = new Intl.DateTimeFormat(this.language, {
								month: 'short',
								year: 'numeric',
							}).format(date);

							this.dateRange.push(formattedString);
							date.setMonth(date.getMonth() + 1);
						}
						break;
				}
			}
		},
	},
};
</script>

<style scoped></style>
