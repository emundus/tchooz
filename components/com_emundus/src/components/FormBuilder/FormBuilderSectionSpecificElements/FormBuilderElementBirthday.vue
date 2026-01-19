<template>
	<div id="form-builder-birthday">
		<div v-if="!loading" class="tw-flex tw-items-center tw-gap-2">
			<select class="form-control" disabled>
				<option v-for="day in 31" :key="day" :value="day">{{ String(day).padStart(2, '0') }}</option>
			</select>
			<span>/</span>
			<select class="form-control" disabled>
				<option v-for="(month, index) in 12" :key="month" :value="index + 1">
					{{ String(month).padStart(2, '0') }}
				</option>
			</select>
			<span>/</span>
			<select class="form-control" disabled>
				<option v-for="year in 100" :key="year" :value="new Date().getFullYear() - year + 1">
					{{ new Date().getFullYear() - year + 1 }}
				</option>
			</select>
		</div>
		<Loader v-else />
	</div>
</template>

<script>
import { useGlobalStore } from '@/stores/global.js';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	components: { Loader },
	props: {
		element: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: false,
		};
	},
	mounted() {
		this.locale = useGlobalStore().getShortLang;
	},
	computed: {
		placeholder() {
			if (this.element.params.jdate_defaulttotoday) {
				// Return current date as placeholder
				const today = new Date();
				const day = String(today.getDate()).padStart(2, '0');
				const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are zero-based
				const year = today.getFullYear();
				return `${day}/${month}/${year}`;
			}

			return this.element.params.jdate_form_format;
		},
	},
};
</script>
<style>
#form-builder-date button {
	border-radius: 0 var(--em-form-br) var(--em-form-br) 0 !important;
	background-color: white;
	color: black;
	border-color: #e0e0e5;
	border-left-color: rgb(224, 224, 229);
	border-left: 0;
}

#form-builder-date input {
	border-radius: var(--em-form-br) 0 0 var(--em-form-br);
}
</style>
