<script>
export default {
	name: 'FormHead',
	props: {
		title: {
			type: String,
			required: true,
		},
		intro: {
			type: String,
		},
	},
};
</script>

<template>
	<div>
		<h1>{{ translate(title) }}</h1>
		<div class="tw-mt-2" v-if="intro">
			<p class="tw-mt-1 tw-text-red-600">
				{{ translate(intro) }}
			</p>
		</div>

		<hr class="tw-mb-4 tw-mt-1.5" />
	</div>
</template>
