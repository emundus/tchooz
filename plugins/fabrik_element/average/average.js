/**
 * IP Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbAverage = new Class({
        Extends: FbElement,
        weight: 0,
        averageOver: 0,
        observeGroupIds: [],

        initialize: function (element, options) {
            this.setPlugin('FbAverage');
            this.parent(element, options);
            this.averageOver = this.options.average_over;
        },

        attachedToForm: function () {
            this.options.elements_to_observe.each(function (element) {
                if (element.weight) {
                    this.weight += parseFloat(element.weight);
                }
            }.bind(this));


            if (this.options.elements_to_observe && this.options.elements_to_observe.length > 0) {
                this.options.elements_to_observe.each(function (element) {
                    this.addObserveEvent(element);
                }.bind(this));
            }

            Fabrik.addEvent('fabrik.form.group.duplicate.end', function(form, event, groupId) {
                this.calc();
            }.bind(this));

            Fabrik.addEvent('fabrik.form.group.delete.end', function(form, event, groupId) {
                this.calc();
            }.bind(this));

            // Stored value may predate a change of observed values or rounding params
            this.calc();

        },

        addObserveEvent: function(element_to_observe) {
            if (element_to_observe === '') {
                return;
            }

            if (this.form.formElements[element_to_observe.element]) {
                this.form.formElements[element_to_observe.element].addNewEventAux(this.form.formElements[element_to_observe.element].getChangeEvent(), function (e) {
                    this.calc(e, element_to_observe);
                }.bind(this));
            } else {

                this.form.repeatGroupMarkers.each(function (index, group_id) {
                    for (let i = 0; i < index; i++) {
                        if (this.form.formElements[element_to_observe.element + '_' + i] && this.form.formElements[element_to_observe.element + '_' + i].groupid == group_id) {
                            this.form.formElements[element_to_observe.element + '_' + i].addNewEventAux(this.form.formElements[element_to_observe.element + '_' + i].getChangeEvent(), function (e) {
                                this.calc(e, element_to_observe.element + '_' + i);
                            }.bind(this));
                        }
                    }
                }.bind(this));
            }
        },

        calc: function(event, element_to_observe) {
            if (this.weight <= 0) {
                return;
            }

            // weighted values: array of {value, weight}
            let weightedValues = [];

            this.options.elements_to_observe.each(function (element) {
                let elementWeight = parseFloat(element.weight) || 0;

                if (this.form.formElements[element.element]) {
                    // normalize value to averageOver
                    let elementValue = (this.getNumericValue(this.form.formElements[element.element]) * this.averageOver) / element.max;
                    weightedValues.push({value: elementValue, weight: elementWeight});
                } else {
                    this.form.repeatGroupMarkers.each(function (index, group_id) {

                        let nb_repeat = 0;
                        let repeat_sum = 0;

                        for (let i = 0; i < index; i++) {
                            if (this.form.formElements[element.element + '_' + i] && this.form.formElements[element.element + '_' + i].groupid == group_id) {
                                repeat_sum += (this.getNumericValue(this.form.formElements[element.element + '_' + i]) * this.averageOver) / element.max;
                                nb_repeat++;
                            }
                        }

                        if (nb_repeat > 0) {
                            // avg of repeat values, then apply weight
                            let repeatAverage = repeat_sum / nb_repeat;
                            weightedValues.push({value: repeatAverage, weight: elementWeight});
                        }
                    }.bind(this));
                }
            }.bind(this));

            // calculate weighted average: sum(value * weight) / sum(weight)
            let weightedSum = 0;
            weightedValues.each(function (item) {
                weightedSum += item.value * item.weight;
            });

            let average = this.roundAverage(weightedSum / this.weight);

            if (this.getValue() === average) {
                return;
            }

            // set average value
            this.update(average);
            this.element.dispatchEvent(new Event('change'));
        },

        getNumericValue: function (elementModel) {
            // Masked inputs (numeric element) display "10,5" or "1 000,5", the unmasked value is always "10.5"
            let value = elementModel.mask && typeof elementModel.mask.unmaskedValue === 'string'
                ? elementModel.mask.unmaskedValue
                : String(elementModel.getValue() ?? '').replace(/[\s\u00A0\u202F]/g, '').replace(',', '.');

            value = parseFloat(value);

            return isNaN(value) ? 0 : value;
        },

        roundAverage: function (value) {
            const decimals = Math.max(0, Math.min(2, parseInt(this.options.decimals ?? 2, 10) || 0));
            const factor = Math.pow(10, decimals);
            // Strip float noise before ceil/floor, otherwise 12.3 * 10 = 123.00000000000001 is ceiled to 124
            const scaled = parseFloat((value * factor).toFixed(6));

            let rounded;
            switch (this.options.rounding) {
                case 'ceil':
                    rounded = Math.ceil(scaled) / factor;
                    break;
                case 'floor':
                    rounded = Math.floor(scaled) / factor;
                    break;
                default:
                    rounded = Math.round(scaled) / factor;
            }

            return rounded.toFixed(decimals);
        }
    });

    return window.FbAverage;
});