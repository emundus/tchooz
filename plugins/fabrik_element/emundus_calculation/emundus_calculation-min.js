/**
 * IP Element
 *
 * @copyright: Copyright (C) 2005-2026  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbEmundus_Calculation = new Class({
        Extends: FbElement,
        elementsToObserve: [],
        elementsValues: {},
        initialize: function (element, options) {
            this.setPlugin('emundus_calculation');
            this.parent(element, options);
            this.elementsToObserve = this.options.elementsToObserve || [];
        },

        attachedToForm: function (cloning = false) {
            if (this.elementsToObserve && this.elementsToObserve.length > 0) {
                this.elementsToObserve.each(function (element) {
                    this.addObserveEvent(element);
                }.bind(this));
            }

            this.calc();

            Fabrik.addEvent('fabrik.form.group.duplicate.end', function(form, event, groupId) {
                this.calc();
            }.bind(this));

            Fabrik.addEvent('fabrik.form.group.delete.end', function(form, event, groupId) {
                this.calc();
            }.bind(this));

        },

        addObserveEvent: function(elementToObserve) {
            if (elementToObserve === '') {
                return;
            }

            if (this.form.formElements[elementToObserve.name]) {
                this.form.formElements[elementToObserve.name].addNewEventAux(this.form.formElements[elementToObserve.name].getChangeEvent(), function (e) {
                    this.calc(e, elementToObserve);
                }.bind(this));
            } else {
                this.form.repeatGroupMarkers.each(function (index, group_id) {
                    if (this.form.formElements[elementToObserve.name + '_' + this.options.repeatCounter] && this.form.formElements[elementToObserve.name + '_' + this.options.repeatCounter].groupid == group_id) {
                        this.form.formElements[elementToObserve.name + '_' + this.options.repeatCounter].addNewEventAux(this.form.formElements[elementToObserve.name + '_' + this.options.repeatCounter].getChangeEvent(), function (e) {
                            this.calc(e, elementToObserve + '_' + this.options.repeatCounter);
                        }.bind(this));
                    }
                }.bind(this));
            }
        },

        calc: function(event, elementToObserve) {
            this.calculateExpression();
        },

        calculateExpression()
        {
            var formData = this.form.getFormElementData();
            var testData = $H(this.form.getFormData(false));

            testData.each(function (v, k) {
                if (k.test(/\[\d+\]$/) || k.test(/^fabrik_vars/)) {
                    formData[k] = v;
                }
            }.bind(this));

            let data = {
                'option'       : 'com_fabrik',
                'format'       : 'raw',
                'task'         : 'plugin.pluginAjax',
                'plugin'       : 'emundus_calculation',
                'method'       : 'ajax_emundus_calculation',
                'element_id'   : this.options.id,
                'formid'       : this.form.id,
                'repeatCounter': this.options.repeatCounter,
            };
            data = Object.append(formData, data);
            Fabrik.loader.start(this.element.getParent(), Joomla.Text._('COM_FABRIK_LOADING'));
            new Request.HTML({
                'url'     : '',
                method    : 'post',
                'data'    : data,
                onSuccess: function (tree, elements, r) {
                    Fabrik.loader.stop(this.element.getParent());
                    this.update(r);

                    if (this.options.validations) {

                        // If we have a validation on the element run it after AJAX calc is done
                        this.form.doElementValidation(this.options.element);
                    }
                    // Fire an onChange event so that js actions can be attached and fired when the value updates
                    this.element.fireEvent('change', new Event.Mock(this.element, 'change'));
                    Fabrik.fireEvent('fabrik.emundus_calculation.update', [this, r]);
                }.bind(this)
            }).send();
        },

        cloned: function (c) {
            this.parent(c);
            this.attachedToForm(true);
        },
    });

    return window.FbEmundus_Calculation;
});