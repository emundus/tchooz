define(['jquery', 'fab/element'],
    function(jQuery, FbElement) {
        window.FbNumeric = new Class({
            Extends: FbElement,
            options: {},
            mask: null,
            initialize: function(element, options) {
                this.setPlugin('numeric');
                this.parent(element, options);
                this.addMask();
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
            }
        });

        return window.FbNumeric;
    }
);