<template>
	<div>
		<div v-for="param in displayedParams" :key="param.name" class="form-group tw-mb-4">
			<label :class="param.type === 'repeatable' ? 'tw-font-bold' : ''">{{ translate(param.label) }}</label>

			<!-- DROPDOWN -->
			<div v-if="param.type === 'dropdown' || param.type === 'sqldropdown'">
				<select
					v-if="repeat_name !== '' && param.options.length > 0 && !param.multiple"
					v-model="element.params[repeat_name][index_name][param.name]"
					class="tw-w-full"
				>
					<option v-for="option in param.options" :key="option.value" :value="option.value">
						{{ translate(option.label) }}
					</option>
				</select>

				<select
					v-else-if="param.options.length > 0 && !param.multiple"
					v-model="element.params[param.name]"
					class="tw-w-full"
				>
					<option v-for="option in param.options" :value="option.value">
						{{ translate(option.label) }}
					</option>
				</select>

				<multiselect
					v-else-if="param.options.length > 0 && param.multiple"
					v-model="element.params[param.name]"
					label="label"
					:custom-label="labelTranslate"
					track-by="value"
					:options="param.options"
					:multiple="true"
					:taggable="false"
					select-label=""
					selected-label=""
					deselect-label=""
					:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_OPTIONS')"
					:close-on-select="false"
					:clear-on-select="false"
					:searchable="true"
					:allow-empty="true"
				></multiselect>
			</div>

			<!-- TEXTAREA -->
			<textarea
				v-else-if="param.type === 'textarea' && repeat_name !== ''"
				v-model="element.params[repeat_name][index_name][param.name]"
				class="tw-w-full"
			></textarea>
			<textarea v-else-if="param.type === 'textarea'" v-model="element.params[param.name]" class="tw-w-full"></textarea>

			<!-- DATABASEJOIN -->
			<div v-else-if="param.type === 'databasejoin' && repeat_name !== ''">
				<select
					v-model="element.params[repeat_name][index_name][param.name]"
					:key="reloadOptions"
					:id="param.name"
					@change="updateDatabasejoinParams"
					class="tw-w-full"
					:class="databasejoin_description ? 'tw-mb-1' : ''"
				>
					<option v-for="option in param.options" :key="option.database_name" :value="option.database_name">
						{{ option.label }}
					</option>
				</select>
				<label v-if="databasejoin_description" style="font-size: small">{{ databasejoin_description }}</label>
			</div>
			<div v-else-if="param.type === 'databasejoin'">
				<select
					v-model="element.params[param.name]"
					:key="reloadOptions"
					:id="param.name"
					@change="updateDatabasejoinParams"
					class="tw-w-full"
					:class="databasejoin_description ? 'tw-mb-1' : ''"
				>
					<option v-for="option in param.options" :key="option.database_name" :value="option.database_name">
						{{ option.label }}
					</option>
				</select>
				<label v-if="databasejoin_description" style="font-size: small">{{ databasejoin_description }}</label>
			</div>

			<div v-else-if="param.type === 'databasejoin_cascade' && repeat_name !== ''">
				<select
					v-model="element.params[repeat_name][index_name][param.name]"
					:key="reloadOptionsCascade"
					class="tw-w-full"
				>
					<option v-for="option in param.options" :key="option.COLUMN_NAME" :value="option.COLUMN_NAME">
						{{ option.COLUMN_NAME }}
					</option>
				</select>
			</div>
			<div v-else-if="param.type === 'databasejoin_cascade'">
				<select v-model="element.params[param.name]" :key="reloadOptionsCascade" class="tw-w-full">
					<option v-for="option in param.options" :key="option.COLUMN_NAME" :value="option.COLUMN_NAME">
						{{ option.COLUMN_NAME }}
					</option>
				</select>
			</div>

			<!-- REPEATABLE -->
			<div v-else-if="param.type === 'repeatable'">
				<div v-for="(repeat_param, key) in Object.entries(element.params[param.name])" :key="key">
					<hr />
					<div class="tw-flex tw-items-center tw-justify-between">
						<label>-- {{ key + 1 }} --</label>
						<button
							v-if="key != 0 && key + 1 == Object.entries(element.params[param.name]).length"
							type="button"
							@click="removeRepeatableField(param.name, key)"
							class="mt-2 w-auto"
						>
							<span class="material-symbols-outlined tw-text-red-600">close</span>
						</button>
					</div>

					<form-builder-element-params
						:key="param.name + key"
						:element="element"
						:params="param.fields"
						:repeat_name="param.name"
						:index="key"
						:databases="databases"
					></form-builder-element-params>
				</div>

				<div class="tw-flex tw-justify-end">
					<button type="button" @click="addRepeatableField(param.name)" class="tw-btn-tertiary tw-mt-2 tw-w-auto">
						{{ translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE') }}
					</button>
				</div>
			</div>

			<div v-else-if="param.type === 'fabrikmodalrepeat'">
				<div v-for="i in element.params[param.name][Object.keys(element.params[param.name])[0]].length" :key="i">
					<hr />
					<div class="tw-flex tw-items-center tw-justify-between">
						<label>-- {{ i }} --</label>
						<button
							v-if="element.params[param.name][Object.keys(element.params[param.name])[0]].length > 1"
							type="button"
							@click="removeFBModalRepeatableField(param.name, i)"
							class="mt-2 w-auto"
						>
							<span class="material-symbols-outlined tw-text-red-600">close</span>
						</button>
					</div>

					<form-builder-element-params
						v-for="sub_field in param.fields"
						:key="sub_field.name"
						:element="element"
						:parent_param="param"
						:params="[sub_field]"
						:databases="databases"
						:repeat_name="param.name"
						:index="i"
					></form-builder-element-params>
				</div>

				<div class="tw-flex tw-justify-end">
					<button
						type="button"
						@click="addFBModalRepeatableField(param.name)"
						class="tw-btn-tertiary tw-mt-2 tw-w-auto"
					>
						{{ translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE') }}
					</button>
				</div>
			</div>

			<!-- ELEMENT SELECTOR -->
			<div v-else-if="param.type === 'listfields'">
				<select v-model="element.params[parent_param.name][param.name][index - 1]" class="tw-w-full">
					<option v-for="option in listFieldsOptions" :key="option.value" :value="option.value">
						{{ option.label }}
					</option>
				</select>
			</div>

			<!-- INPUT (TEXT,NUMBER) -->
			<input
				v-else-if="parent_param && parent_param.name"
				v-model="element.params[parent_param.name][param.name][index - 1]"
				class="tw-w-full"
				:placeholder="translate(param.placeholder)"
			/>
			<input
				v-else-if="repeat_name !== ''"
				:type="param.type"
				v-model="element.params[repeat_name][index_name][param.name]"
				class="tw-w-full"
				:placeholder="translate(param.placeholder)"
			/>
			<input
				v-else
				:type="param.type"
				v-model="element.params[param.name]"
				class="tw-w-full"
				:placeholder="translate(param.placeholder)"
			/>

			<!-- HELPTEXT -->
			<label v-if="param.helptext !== ''" style="font-size: small">{{ translate(param.helptext) }}</label>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
/* IMPORT YOUR COMPONENTS */
import Multiselect from 'vue-multiselect';

/* IMPORT YOUR SERVICES */
import formBuilderService from '@/services/formbuilder';
import { useGlobalStore } from '@/stores/global.js';
import { useFormBuilderStore } from '@/stores/formbuilder.js';
import FormBuilderElementOptions from '@/components/FormBuilder/FormBuilderSectionSpecificElements/FormBuilderElementOptions.vue';

export default {
	name: 'FormBuilderElementParams',
	components: { FormBuilderElementOptions, Multiselect },
	props: {
		element: {
			type: Object,
			required: false,
		},
		params: {
			type: Array,
			required: false,
		},
		databases: {
			type: Array,
			required: false,
		},
		repeat_name: {
			type: String,
			required: false,
			default: '',
		},
		index: {
			type: Number,
			required: false,
			default: 0,
		},
		parent_param: {
			type: Object,
			required: false,
		},
	},
	data: () => ({
		databasejoin_description: null,
		reloadOptions: 0,
		reloadOptionsCascade: 0,

		idElement: 0,
		loading: false,
	}),
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},
	created() {
		this.params.forEach((param) => {
			if (param.type === 'fabrikmodalrepeat') {
				this.element.params[param.name] = JSON.parse(this.element.params[param.name]);
			}

			if (param.type === 'databasejoin') {
				param.options = this.databases;
				if (this.element.params['join_db_name'] !== '') {
					this.updateDatabasejoinParams();
				}
			}

			if (param.type === 'sqldropdown') {
				this.loading = true;
				this.getSqlDropdownOptions(param);
			}

			if (param.type === 'dropdown') {
				if (param.getOptionsTask) {
					formBuilderService.getDropdownOptions(param.getOptionsTask).then((response) => {
						param.options = response.data;
					});
				}
			}

			if (param.reload_on_change) {
				// find param to watch
				let param_to_watch = this.params.find((p) => p.name === param.reload_on_change);

				if (param_to_watch) {
					this.$watch(
						() => this.element.params[param_to_watch.name],
						(newValue, oldValue) => {
							if (newValue !== oldValue) {
								this.loading = true;
								this.getSqlDropdownOptions(param);
							}
						},
					);
				}
			}
		});
	},
	methods: {
		getSqlDropdownOptions(param) {
			let table = param.table;
			let key = param.key;
			let value = param.value;

			if (param.table.includes('{')) {
				let param_name = param.table
					.match(/\{(.*?)\}/g)[0]
					.replace('{', '')
					.replace('}', '');
				table = this.element.params[param_name];
			}
			if (param.key.includes('{')) {
				let param_name = param.key
					.match(/\{(.*?)\}/g)[0]
					.replace('{', '')
					.replace('}', '');
				key = this.element.params[param_name];
			}
			if (param.value.includes('{')) {
				let param_name = param.value
					.match(/\{(.*?)\}/g)[0]
					.replace('{', '')
					.replace('}', '');
				value = this.element.params[param_name];
			}

			if (table.includes('{') || key.includes('{') || value.includes('{')) {
				return;
			}

			formBuilderService.getSqlDropdownOptions(table, key, value, param.translate).then((response) => {
				param.options = response.data;

				if (
					param.multiple == true &&
					this.element.params[param.name] &&
					typeof this.element.params[param.name] === 'string' &&
					this.element.params[param.name].length > 0
				) {
					let ids_to_exclude = this.element.params[param.name].split(',');
					const regex = /\'|"/gi;

					this.element.params[param.name] = [];

					ids_to_exclude.forEach((id) => {
						id = id.replace(regex, '');
						let option = param.options.find((option) => id == option.value);

						if (option) {
							this.element.params[param.name].push(option);
						}
					});
				} else if (param.multiple != true) {
					// let the value as it is
				} else {
					this.element.params[param.name] = [];
				}

				this.loading = false;
			});
		},

		updateDatabasejoinParams() {
			if (!this.sysadmin) {
				const index = this.databases.map((e) => e.database_name).indexOf(this.element.params['join_db_name']);

				if (index !== -1) {
					let database = this.databases[index];
					this.element.params['join_key_column'] = database.join_column_id;
					if (database.translation == 1) {
						this.element.params['join_val_column'] = database.join_column_val + '_fr';
						this.element.params['join_val_column_concat'] = '{thistable}.' + database.join_column_val + '_{shortlang}';
					} else {
						this.element.params['join_val_column'] = database.join_column_val;
						this.element.params['join_val_column_concat'] = '';
					}
					this.databasejoin_description = this.databases[index].description;
				} else {
					let index = this.params.map((e) => e.name).indexOf('join_db_name');
					let new_option = {
						label: this.element.params['join_db_name'],
						database_name: this.element.params['join_db_name'],
					};
					this.params[index].options.push(new_option);
					setTimeout(() => {
						document.getElementById('join_db_name').disabled = true;
					}, 500);
				}
			} else {
				formBuilderService.getDatabaseJoinOrderColumns(this.element.params['join_db_name']).then((response) => {
					let database = null;
					const indexDatabase = this.databases.map((e) => e.database_name).indexOf(this.element.params['join_db_name']);
					if (indexDatabase !== -1) {
						database = this.databases[indexDatabase];
					}

					let index = this.params.map((e) => e.name).indexOf('join_key_column');
					this.params[index].options = response.data;
					this.element.params['join_key_column'] = database
						? database.join_column_id
						: this.params[index].options[0].COLUMN_NAME;

					index = this.params.map((e) => e.name).indexOf('join_val_column');
					this.params[index].options = response.data;
					this.element.params['join_val_column'] = database
						? database.join_column_val
						: this.params[index].options[0].COLUMN_NAME;

					this.element.params['join_val_column_concat'] = '';
					if (database && database.translation == 1) {
						this.element.params['join_val_column'] = database.join_column_val + '_fr';
						this.element.params['join_val_column_concat'] = '{thistable}.' + database.join_column_val + '_{shortlang}';
					}

					if (
						this.element.params['database_join_where_sql'] === '' ||
						this.element.params['database_join_where_sql'] === null ||
						typeof this.element.params['database_join_where_sql'] === 'undefined'
					) {
						this.element.params['database_join_where_sql'] = '';
						let publishedColumn = this.params[index].options.find((option) => option.COLUMN_NAME === 'published');
						if (typeof publishedColumn !== 'undefined') {
							this.element.params['database_join_where_sql'] = 'WHERE {thistable}.published = 1 ';
						}

						this.element.params['database_join_where_sql'] +=
							'ORDER BY {thistable}.' + this.element.params['join_key_column'];
					}

					this.reloadOptionsCascade += 1;
				});
			}
		},
		addRepeatableField(param) {
			let index = Object.entries(this.element.params[param]).length;
			this.element.params[param][param + index] = {};
			//this.element.params[param][param+index] = this.element.params[param][param+'0'];

			this.$forceUpdate();
		},
		removeRepeatableField(param, key) {
			delete this.element.params[param][param + key];
			this.$forceUpdate();
		},
		removeFBModalRepeatableField(paramName, index) {
			const entries = Object.keys(this.element.params[paramName]);

			entries.forEach((entry) => {
				this.element.params[paramName][entry].splice(index - 1, 1);
			});

			this.$forceUpdate();
		},
		addFBModalRepeatableField(paramName) {
			const keys = Object.keys(this.element.params[paramName]);

			keys.forEach((key) => {
				this.element.params[paramName][key].push('');
			});
		},
		labelTranslate({ label }) {
			return this.translate(label);
		},
	},
	computed: {
		sysadmin: function () {
			return parseInt(this.globalStore.hasSysadminAccess);
		},
		index_name: function () {
			return this.repeat_name !== '' ? this.repeat_name + this.index : '';
		},
		displayedParams() {
			return this.params.filter((param) => {
				return (param.published && !param.sysadmin_only) || (this.sysadmin && param.sysadmin_only && param.published);
			});
		},
		listFieldsOptions() {
			return useFormBuilderStore().getPageElements.filter((element) => {
				return (
					element.publish &&
					this.element.id != element.element_id &&
					['field', 'calc', 'average', 'dropdown', 'radiobutton'].includes(element.plugin) &&
					!['parent_id'].includes(element.name)
				);
			});
		},
	},
};
</script>
