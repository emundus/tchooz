/**
 * FieldToHtmlFactory
 *
 * Transforms Tchooz Field schemas (from PHP toSchema()) into HTML form elements.
 * Supported types: string, choice, numeric, boolean, date, password
 *
 * Usage:
 *   const html = FieldToHtmlFactory.buildForm(action.parameters);
 *   const values = FieldToHtmlFactory.extractValues(action.parameters, containerElement);
 *   const errors = FieldToHtmlFactory.validate(action.parameters, containerElement);
 */
const FieldToHtmlFactory = {

    /**
     * Build an HTML form string from an array of Field schemas
     * @param {Array} parameters - Array of field schemas (from PHP toSchema())
     * @returns {string} HTML string
     */
    buildForm(parameters) {
        if (!parameters || parameters.length === 0) {
            return '';
        }

        const fields = parameters.map(param => this.buildField(param));
        return `<div class="em-action-form tw-flex tw-flex-col tw-gap-4 tw-text-left">${fields.join('')}</div>`;
    },

    /**
     * Build a single field HTML based on its type
     * @param {Object} schema - The field schema
     * @returns {string} HTML string for the field
     */
    buildField(schema) {
        const builder = this.builders[schema.type];
        if (!builder) {
            console.warn(`FieldToHtmlFactory: unknown field type "${schema.type}"`);
            return '';
        }

        const requiredAttr = schema.required ? 'required' : '';
        const requiredMark = schema.required ? '<span class="tw-text-red-500"> *</span>' : '';
        const label = schema.label
            ? `<label class="tw-font-medium tw-mb-1 tw-block" for="field-${schema.name}">${schema.label}${requiredMark}</label>`
            : '';

        const input = builder.call(this, schema, requiredAttr);

        return `<div class="em-action-field tw-flex tw-flex-col" data-field-name="${schema.name}">${label}${input}</div>`;
    },

    /**
     * Field builders mapped by type
     */
    builders: {
        string(schema, requiredAttr) {
            const minLength = schema.minLength ? `minlength="${schema.minLength}"` : '';
            const maxLength = schema.maxLength ? `maxlength="${schema.maxLength}"` : '';
            return `<input type="text" id="field-${schema.name}" name="${schema.name}" class="tw-border tw-rounded tw-p-2 tw-w-full" ${requiredAttr} ${minLength} ${maxLength} />`;
        },

        choice(schema, requiredAttr) {
            const multiple = schema.multiple ? 'multiple' : '';
            const options = (schema.choices || []).map(choice => {
                return `<option value="${choice.value ?? ''}">${choice.label}</option>`;
            }).join('');

            return `<select id="field-${schema.name}" name="${schema.name}" class="tw-border tw-rounded tw-p-2 tw-w-full" ${requiredAttr} ${multiple}>${options}</select>`;
        },

        numeric(schema, requiredAttr) {
            const min = schema.min !== undefined && schema.min !== null ? `min="${schema.min}"` : '';
            const max = schema.max !== undefined && schema.max !== null ? `max="${schema.max}"` : '';
            return `<input type="number" id="field-${schema.name}" name="${schema.name}" class="tw-border tw-rounded tw-p-2 tw-w-full" ${requiredAttr} ${min} ${max} />`;
        },

        boolean(schema) {
            return `<label class="tw-flex tw-items-center tw-gap-2 tw-cursor-pointer">
                <input type="checkbox" id="field-${schema.name}" name="${schema.name}" class="tw-rounded" />
                <span>${schema.label || ''}</span>
            </label>`;
        },

        date(schema, requiredAttr) {
            return `<input type="date" id="field-${schema.name}" name="${schema.name}" class="tw-border tw-rounded tw-p-2 tw-w-full" ${requiredAttr} />`;
        },

        password(schema, requiredAttr) {
            return `<input type="password" id="field-${schema.name}" name="${schema.name}" class="tw-border tw-rounded tw-p-2 tw-w-full" ${requiredAttr} />`;
        },

        radio(schema) {
            const options = (schema.choices || []).map(choice => {
                return `<label class="tw-flex tw-items-center tw-gap-2 tw-cursor-pointer">
                    <input type="radio" name="${schema.name}" value="${choice.value ?? ''}" />
                    <span>${choice.label}</span>
                </label>`;
            }).join('');

            return `<div id="field-${schema.name}" class="tw-flex tw-flex-col tw-gap-1">${options}</div>`;
        }
    },

    /**
     * Extract values from the rendered form
     * @param {Array} parameters - Array of field schemas
     * @param {HTMLElement} container - The Swal popup container
     * @returns {Object} key-value pairs of field name to value
     */
    extractValues(parameters, container) {
        const values = {};
        if (!parameters || !container) {
            return values;
        }

        parameters.forEach(param => {
            const el = container.querySelector(`#field-${param.name}`);
            if (!el) {
                return;
            }

            switch (param.type) {
                case 'boolean':
                    values[param.name] = el.checked ? '1' : '0';
                    break;
                case 'choice':
                    if (param.multiple) {
                        values[param.name] = Array.from(el.selectedOptions).map(opt => opt.value);
                    } else {
                        values[param.name] = el.value;
                    }
                    break;
                case 'radio': {
                    const checked = container.querySelector(`input[name="${param.name}"]:checked`);
                    values[param.name] = checked ? checked.value : '';
                    break;
                }
                default:
                    values[param.name] = el.value;
                    break;
            }
        });

        return values;
    },

    /**
     * Validate form values based on field schemas
     * @param {Array} parameters - Array of field schemas
     * @param {HTMLElement} container - The Swal popup container
     * @returns {string|null} Error message string or null if valid
     */
    validate(parameters, container) {
        if (!parameters || !container) {
            return null;
        }

        for (const param of parameters) {
            const values = this.extractValues([param], container);
            const value = values[param.name];

            if (param.required) {
                const isEmpty = value === '' || value === undefined || value === null
                    || (Array.isArray(value) && value.length === 0);

                if (isEmpty) {
                    return `${param.label} is required`;
                }
            }

            if (param.type === 'string') {
                if (param.minLength && value.length < param.minLength) {
                    return `${param.label} must be at least ${param.minLength} characters long`;
                }
                if (param.maxLength && value.length > param.maxLength) {
                    return `${param.label} must be at most ${param.maxLength} characters long`;
                }
            }
        }

        return null;
    }
};

