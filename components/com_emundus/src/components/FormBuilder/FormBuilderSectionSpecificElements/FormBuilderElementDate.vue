<template>
	<div id="form-builder-date">
		<div v-if="!loading" class="tw-flex">
			<input type="text" class="form-control" readonly :placeholder="placeholder" />
			<button
				type="button"
				class="btn btn-primary"
				data-date-format="%d/%m/%Y"
				data-firstday="1"
				data-weekend="0,6"
				data-today-btn="1"
				data-week-numbers="0"
				data-show-time="0"
				data-show-others="1"
				data-time24="24"
				data-only-months-nav="0"
				data-min-year=""
				data-max-year=""
				data-date-type="gregorian"
			>
				<span class="icon-calendar" aria-hidden="true"></span>
				<span class="visually-hidden">Ouvrir le calendrier</span>
			</button>
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
