/**
 * Booking Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElementList) {
    window.FbOrderlist = new Class({
        Extends   : FbElementList,
        initialize: function (element, options) {
            this.setPlugin('orderlist');
            this.parent(element, options);

            window.addEventListener('message', (event) => {
                if (event.data.type === 'orderListUpdated' && (event.data.elementId === this.element.id))
                {
                    this.update(event.data.value);
                    this.element.dispatchEvent(new Event('change'));
                }
            });
        },
        cloned: function (c) {
            this.parent(c);
            const element = document.getElementById(this.element.id);
            const vueElement = element.parentElement.parentElement.parentElement;

            let data = JSON.parse(vueElement.getAttribute('data'));
            data.elementId = this.element.id;
            data.elementName = this.element.name;
            vueElement.removeAttribute('data-v-app');
            vueElement.setAttribute('data', JSON.stringify(data));
            vueElement.id = this.element.id + '-container';

            // remove vueElement children and re-render
            while (vueElement.firstChild) {
                vueElement.removeChild(vueElement.firstChild);
            }

            // reload <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>
            const script = document.createElement('script');
            script.type = 'module';
            script.src = '/media/com_emundus_vue/app_emundus.js?' + new Date().getTime();
            document.body.appendChild(script);
        },
    });

    return window.FbOrderlist;
});