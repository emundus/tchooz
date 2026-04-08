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
                    let elementValue = this.form.formElements[element.element].getValue();

                    if (isNaN(elementValue) || elementValue === '') {
                        weightedValues.push({value: 0, weight: elementWeight});
                    } else {
                        // normalize value to averageOver
                        elementValue = (parseFloat(elementValue) * this.averageOver) / element.max;
                        weightedValues.push({value: parseFloat(elementValue), weight: elementWeight});
                    }
                } else {
                    this.form.repeatGroupMarkers.each(function (index, group_id) {

                        let nb_repeat = 0;
                        let repeat_sum = 0;

                        for (let i = 0; i < index; i++) {
                            if (this.form.formElements[element.element + '_' + i] && this.form.formElements[element.element + '_' + i].groupid == group_id) {
                                let elementValue = this.form.formElements[element.element + '_' + i].getValue();

                                if (isNaN(elementValue) || elementValue === '') {
                                    repeat_sum += 0;
                                } else {
                                    elementValue = (parseFloat(elementValue) * this.averageOver) / element.max;
                                    repeat_sum += parseFloat(elementValue);
                                }
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

            let average = parseFloat(weightedSum / this.weight).toFixed(2);

            // set average value
            this.update(average);
            this.element.dispatchEvent(new Event('change'));
        }
    });

    return window.FbAverage;
});