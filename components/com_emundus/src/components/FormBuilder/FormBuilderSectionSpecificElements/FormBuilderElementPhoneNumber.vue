<template>
	<div id="form-builder-phone-number">
		<div v-if="loading" class="em-loader"></div>
		<div v-else>
			<div class="tw-flex tw-items-center">
				<div class="country-select tw-flex tw-h-10 tw-items-center tw-justify-center tw-p-2">
					<img
						v-if="element.params.default_country"
						:src="'../../../../../../images/emundus/flags/' + defaultCountryLowerCased + '.png'"
					/>
					<img v-else src="../../../../../../images/emundus/flags/fr.png" />
				</div>
				<div class="tw-w-full">
					<input class="phonenumber" readonly type="text" value="+33 6 12 34 56 78" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default {
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

			editable: false,
			dynamicComponent: 0,
		};
	},
	created() {},
	methods: {},
	computed: {
		defaultCountryLowerCased() {
			if (this.element.params.default_country && typeof this.element.params.default_country === 'string') {
				return this.element.params.default_country.toLowerCase();
			} else {
				return this.element.params.default_country[0].value.toLowerCase();
			}
		},
	},
	watch: {},
};
</script>

<style lang="scss">
#form-builder-phone-number {
	.country-select {
		height: 42px;
		width: 80px;
		border-top: solid 1px;
		border-bottom: solid 1px;
		border-left: solid 1px;
		border-color: #e0e0e5;
		border-top-left-radius: var(--em-coordinator-br);
		border-bottom-left-radius: var(--em-coordinator-br);

		img {
			width: 24px;
		}
	}

	.phonenumber {
		height: 42px;
		border-top-left-radius: 0;
		border-bottom-left-radius: 0;
	}
}
</style>
