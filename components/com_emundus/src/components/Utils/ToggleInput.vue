<script>
export default {
	name: 'ToggleInput',
	props: {
		id: {
			type: String,
			required: true,
		},
		value: {
			type: [String, Number],
			default: '0',
		},
	},
	emits: ['update:value'],
};
</script>

<template>
	<div class="em-toggle">
		<input
			type="checkbox"
			true-value="1"
			false-value="0"
			class="em-toggle-check"
			:id="id"
			:value="value"
			:checked="value == '1' || value == 1"
			@change="$emit('update:value', $event.target.checked)"
		/>
		<strong class="b em-toggle-switch"></strong>
		<strong class="b em-toggle-track"></strong>
	</div>
</template>

<style scoped></style>
