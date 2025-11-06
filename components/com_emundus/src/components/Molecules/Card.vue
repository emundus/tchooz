<script>
export default {
	name: 'Card',
	props: {
		legendColor: {
			type: String,
			default: null,
		},
	},
};
</script>

<template>
	<div
		class="tw-flex tw-h-full tw-min-h-[290px] tw-flex-col tw-justify-between tw-rounded-applicant-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<div class="card-content">
			<div class="tw-flex tw-items-center tw-justify-between">
				<div class="legend-tag tw-min-h-[38px]" :style="{ color: legendColor }">
					<slot name="legend" />
				</div>
				<div>
					<slot name="legend_2" />
				</div>
			</div>
			<h3 class="card-title">
				<slot name="title" />
			</h3>
			<div class="informations">
				<slot name="information_1" />
				<slot name="information_2" />
			</div>
			<hr />
			<div class="description">
				<slot name="description" />
			</div>
		</div>
		<div class="actions tw-mt-auto">
			<slot name="actions" />
		</div>
	</div>
</template>

<style scoped>
.legend-tag {
	font-size: 12px;
	font-weight: 600;
	font-family: var(--em-profile-font), Inter, sans-serif;
	margin-bottom: 0;
	-webkit-line-clamp: 2;
	overflow: hidden;
	-webkit-box-orient: vertical;
	max-height: 48px;
	display: -webkit-box;
	line-height: 140%;
}

.card-title {
	margin-bottom: 8px !important;
	color: var(--em-default-title-color-1) !important;
	-webkit-line-clamp: 2;
	overflow: hidden;
	-webkit-box-orient: vertical;
	display: -webkit-box;
	line-height: 140%;
	min-height: 46px;
	font-family: var(--em-profile-font-title), Inter, sans-serif !important;
}
</style>
