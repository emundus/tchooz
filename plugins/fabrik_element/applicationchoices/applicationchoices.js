define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbApplicationChoices = new Class({
        Extends: FbElement,

        /**
         * Initialize object from Fabrik/Joomla
         *
         * @param element       string      name of the element
         * @param options       array       all options for the element
         */
        initialize: function (element, options) {
            this.setPlugin('applicationchoices');
            this.parent(element, options);

            if(options.layout === 'form') {
                var choices = document.getElementById(this.element.id + '_choice');
                choices.addEventListener('change', (e) => {
                    this.choice = e.target.value;
                    this.updateValue(e);
                });

                var statuses = document.getElementById(this.element.id + '_status');
                if(statuses) {
                    statuses.addEventListener('change', (e) => {
                        this.status = e.target.value;
                        this.updateValue(e);
                    });
                }
            }
        },

        /**
         * Called when element cloned in repeatable group
         *
         * @param   c       int         index of the new element
         */
        cloned: function (c) {
            this.parent(c);

            var parentElement = this.element.parentElement;
            parentElement.id = this.element.id + '_container';

            var choice_select = parentElement.querySelector('select[id*="_choice"]');
            if(choice_select)
            {
                choice_select.id = this.element.id + '_choice';
                choice_select.name = this.element.name + '_choice';

                choice_select.addEventListener('change', (e) => {
                    this.choice = e.target.value;
                    this.updateValue(e);
                });
            }

            var status_select = parentElement.querySelector('select[id*="_status"]');
            if(status_select)
            {
                status_select.id = this.element.id + '_status';
                status_select.name = this.element.name + '_status';

                status_select.addEventListener('change', (e) => {
                    this.status = e.target.value;
                    this.updateValue(e);
                });
            }
        },

        updateValue: function(element) {
            var id = this.element.id;

            if(element) {
                id = element.srcElement.id;
                id = id.replace('_choice', '').replace('_status', '');
            }

            var choiceElement = document.getElementById(id + '_choice');
            var combinedValue = choiceElement.value;

            if(this.options.confirmation == 1) {
                var statusElement = document.getElementById(id + '_status');
                combinedValue += '|' + statusElement.value;
            }

            document.getElementById(id).value = combinedValue;
        }
    });

    return window.FbApplicationChoices;
});