<script>
export default {
	name: 'GridDetails',
};
</script>

<template>
	<div
		class="tw-mt-6 tw-grid tw-grid-cols-3 tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-px-2 tw-py-3"
	>
		<div class="tw-border-r tw-border-neutral-400 tw-px-2 tw-text-center">
			<label class="tw-text-neutral-600">
				<slot name="label_1"></slot>
			</label>
			<div class="tw-mt-2">
				<slot name="value_1"></slot>
			</div>
		</div>
		<div class="tw-border-r tw-border-neutral-400 tw-px-2 tw-text-center">
			<label class="tw-text-neutral-600">
				<slot name="label_2"></slot>
			</label>
			<div class="tw-mt-2">
				<slot name="value_2"></slot>
			</div>
		</div>
		<div class="tw-px-2 tw-text-center">
			<label class="tw-text-neutral-600">
				<slot name="label_3"></slot>
			</label>
			<div class="tw-mt-2">
				<slot name="value_3"></slot>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
