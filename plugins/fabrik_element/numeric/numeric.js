define(['jquery', 'fab/element'],
    function(jQuery, FbElement) {
        window.FbNumeric = new Class({
            Extends: FbElement,
            options: {},

            initialize: function(element, options) {
                this.setPlugin('numeric');
                this.parent(element, options);

                this.addMask();
            },

            cloned: function(c) {
                this.mask = null;
                this.addMask();

                this.parent(c);
            },

            addMask: function () {
                if (this.mask) {
                    this.mask.destroy();
                }

                this.mask = IMask(
                    this.element,
                    {
                        mask: Number,
                        scale: this.options.format.decimal_number,
                        thousandsSeparator:  this.options.format.thousand_separator,
                        radix: this.options.format.decimal_separator || ',',
                        mapToRadix: ['.'],
                        normalizeZeros: true,
                        min: this.options.format.min || Number.MIN_SAFE_INTEGER,
                        max: this.options.format.max || Number.MAX_SAFE_INTEGER,
                    }
                );
            },

            update: function(e)
            {
                if (typeOf(this.element) === 'null') {
                    return;
                }
                this.setValue(e);
            },

            setValue: function (val)
            {
                // Necessary for cloned
                this.mask.value = val;
            },
        });

        return window.FbNumeric;
    }
);