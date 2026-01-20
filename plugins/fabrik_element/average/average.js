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
                    this.weight += parseInt(element.weight);
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
            if (this.weight < 1) {
                return;
            }

            // get all elements to observe values
            let values = [];

            this.options.elements_to_observe.each(function (element) {
                if (this.form.formElements[element.element]) {
                    let elementValue = this.form.formElements[element.element].getValue();

                    if (isNaN(elementValue) || elementValue === '') {
                        values.push(0);
                    } else {
                        // normalize value to averageOver
                        elementValue = (parseFloat(elementValue) * this.averageOver) / element.max;

                        // push element as much as its weight, and normalized to averageOver
                        for (var i = 0; i < parseInt(element.weight); i++) {
                            values.push(parseFloat(elementValue));
                        }
                    }
                } else {
                    this.form.repeatGroupMarkers.each(function (index, group_id) {

                        let nb_repeat = 0;
                        let repeat_values = [];

                        for (let i = 0; i < index; i++) {
                            if (this.form.formElements[element.element + '_' + i] && this.form.formElements[element.element + '_' + i].groupid == group_id) {
                                let elementValue = this.form.formElements[element.element + '_' + i].getValue();

                                if (isNaN(elementValue) || elementValue === '') {
                                    repeat_values.push(0);
                                } else {
                                    elementValue = (parseFloat(elementValue) * this.averageOver) / element.max;

                                    // push element as much as its weight
                                    for (var j = 0; j < parseInt(element.weight); j++) {
                                        repeat_values.push(parseFloat(elementValue));
                                    }
                                }
                                nb_repeat++;
                            }
                        }

                        if (nb_repeat > 0) {
                            // avg of repeat_values
                            let sum = 0;
                            repeat_values.each(function (value) {
                                sum += parseFloat(value);
                            });

                            let average = parseFloat(sum / nb_repeat).toFixed(2);
                            values.push(average);
                        }
                    }.bind(this));
                }
            }.bind(this));

            // calculate average
            let sum = 0;
            values.each(function (value) {
                sum += parseFloat(value);
            });

            let average = parseFloat(sum / this.weight).toFixed(2);

            // set average value
            this.update(average);
            this.element.dispatchEvent(new Event('change'));
        }
    });

    return window.FbAverage;
});