/**
 * PickList Element 
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbPicklist = new Class({
        Extends: FbElement,
        initialize: function (element, options) {
            this.setPlugin('fabrikpicklist');
            this.parent(element, options);
            if (this.options.allowadd === true) {
                this.watchAddToggle();
                this.watchAdd();
            }
            this.makeSortable();
            this.addMobileSupport();
        },

        /**
         * Add minimal mobile touch support
         */
        addMobileSupport: function () {
            if (!this.options.editable) return;

            var c = this.getContainer();
            var from = c.getElement('.fromList');
            var to = c.getElement('.toList');

            if (!from || !to) return;

            var that = this;

            [from, to].each(function (list) {
                list.addEvent('touchstart', function (e) {
                    var target = e.target;
                    if (target.tagName !== 'LI' || target.hasClass('emptypicklist')) {
                        return;
                    }
                    e.preventDefault();
                    that.handleTouch(e, target, from, to);
                });
            });
        },

        handleTouch: function (e, item, from, to) {
            var that = this;
            var touch = e.touches[0];

            // Make original semi-transparent
            var originalOpacity = item.getStyle('opacity');
            item.setStyle('opacity', '0.3');

            // Clone for dragging
            var clone = item.clone();
            var rect = item.getBoundingClientRect();

            clone.setStyles({
                'position': 'fixed',
                'left': rect.left + 'px',
                'top': rect.top + 'px',
                'width': rect.width + 'px',
                'z-index': '9999',
                'pointer-events': 'none',
                'border': '2px solid #40a9ff',
                'box-shadow': '0 2px 8px rgba(0,0,0,0.2)'
            });

            document.body.adopt(clone);

            var currentTarget = null;

            var onMove = function (e) {
                e.preventDefault();
                var t = e.touches[0];

                // Move clone
                clone.setStyles({
                    'left': (t.clientX - rect.width / 2) + 'px',
                    'top': (t.clientY - rect.height / 2) + 'px'
                });

                // Check drop target
                var overFrom = that.isOverElement(t.clientX, t.clientY, from);
                var overTo = that.isOverElement(t.clientX, t.clientY, to);

                var newTarget = overFrom ? from : (overTo ? to : null);

                if (newTarget !== currentTarget) {
                    if (currentTarget) {
                        currentTarget.setStyle('background-color', '');
                    }
                    if (newTarget) {
                        newTarget.setStyle('background-color', '#e3f2fd');
                    }
                    currentTarget = newTarget;
                }
            };

            var onEnd = function (e) {
                e.preventDefault();
                document.removeEvent('touchmove', onMove);
                document.removeEvent('touchend', onEnd);

                clone.destroy();
                item.setStyle('opacity', originalOpacity);

                // Reset background
                from.setStyle('background-color', '');
                to.setStyle('background-color', '');

                // Move item if valid drop
                if (currentTarget && currentTarget !== item.getParent()) {
                    currentTarget.adopt(item);
                    that.setData();
                    that.showNotices();
                }
            };

            document.addEvent('touchmove', onMove);
            document.addEvent('touchend', onEnd);
        },

        isOverElement: function (x, y, element) {
            var rect = element.getBoundingClientRect();
            return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
        },

        /**
         * Ini the sortable object
         */
        makeSortable: function () {
            if (this.options.editable) {
                var c = this.getContainer();
                var from = c.getElement('.fromList'),
                    to = c.getElement('.toList'),
                    dropcolour = from.getStyle('background-color'),
                    that = this;
                this.sortable = new Sortables([from, to], {
                    clone: true,
                    revert: true,
                    opacity: 0.7,
                    hovercolor: this.options.bghovercolour,
                    onComplete: function (element) {
                        this.setData();
                        this.showNotices(element);
                        that.fadeOut(from, dropcolour);
                        that.fadeOut(to, dropcolour);
                    }.bind(this),
                    onSort: function (element, clone) {
                        this.showNotices(element, clone);

                    }.bind(this),


                    onStart: function (element, clone) {
                        this.drag.addEvent('onEnter', function (element, droppable) {
                            if (this.lists.contains(droppable)) {
                                that.fadeOut(droppable, this.options.hovercolor);
                                if (this.lists.contains(this.drag.overed)) {
                                    this.drag.overed.addEvent('mouseleave', function () {
                                        that.fadeOut(from, dropcolour);
                                        that.fadeOut(to, dropcolour);
                                    }.bind(this));
                                }
                            }
                        }.bind(this));
                    }
                });
                var notices = [from.getElement('li.emptypicklist'), to.getElement('li.emptypicklist')];
                this.sortable.removeItems(notices);
                this.showNotices();
            }
        },

        fadeOut: function (droppable, colour) {
            var hoverFx = new Fx.Tween(droppable, {
                wait: false,
                duration: 600
            });
            hoverFx.start('background-color', colour);
        },

        /**
         * Show empty notices
         *
         * @param  DOMNode  element  Li being dragged
         *
         */
        showNotices: function (element, clone) {
            if (element) {
                // Get list
                element = element.getParent('ul');
            }
            var c = this.getContainer(),
                limit, to, i;
            var lists = [c.getElement('.fromList'), c.getElement('.toList')];
            for (i = 0; i < lists.length; i++) {
                to = lists[i];
                limit = (to === element || typeOf(element) === 'null') ? 1 : 2;
                var notice = to.getElement('li.emptypicklist');
                var lis = to.getElements('li');
                lis.length > limit ? notice.hide() : notice.show();
            }
        },

        setData: function () {
            var c = this.getContainer(),
                to = c.getElement('.toList'),
                lis = to.getElements('li[class!=emptypicklist]'),
                v = lis.map(
                    function (item, index) {
                        return item.id
                            .replace(this.options.element + '_value_', '');
                    }.bind(this));
            this.element.value = JSON.stringify(v);
        },

        watchAdd: function () {
            var id = this.element.id,
                c = this.getContainer(),
                to = c.getElement('.toList'),
                btn = c.getElement('input[type=button]');

            if (typeOf(btn) === 'null') {
                return;
            }
            btn.addEvent(
                'click',
                function (e) {
                    var val;
                    value = c.getElement('input[name=addPicklistValue]'),
                        labelEl = c.getElement('input[name=addPicklistLabel]'),
                        label = labelEl.get('value');
                    if (typeOf(value) !== 'null') {
                        val = value.value;
                    } else {
                        val = label;
                    }
                    if (val === '' || label === '') {
                        alert(Joomla.JText._('PLG_ELEMENT_PICKLIST_ENTER_VALUE_LABEL'));
                    } else {

                        var li = new Element('li', {
                            'class': 'picklist',
                            'id': this.element.id + '_value_' + val
                        }).set('text', label);

                        to.adopt(li);
                        this.sortable.addItems(li);

                        e.stop();
                        if (typeOf(value) === 'element') {
                            value.value = '';
                        }
                        labelEl.value = '';
                        this.setData();
                        this.addNewOption(val, label);
                        this.showNotices();
                    }
                }.bind(this));
        },

        unclonableProperties: function () {
            return ['form', 'sortable'];
        },

        watchAddToggle: function () {
            var c = this.getContainer();
            var d = c.getElement('div.addoption');
            var a = c.getElement('.toggle-addoption');
            if (this.mySlider) {
                // Copied in repeating group so need to remove old slider html first
                var clone = d.clone();
                var fe = c.getElement('.fabrikElement');
                d.getParent().destroy();
                fe.adopt(clone);
                d = c.getElement('div.addoption');
                d.setStyle('margin', 0);
            }
            this.mySlider = new Fx.Slide(d, {
                duration: 500
            });
            this.mySlider.hide();
            a.addEvent('click', function (e) {
                e.stop();
                this.mySlider.toggle();
            }.bind(this));
        },

        cloned: function (c) {
            delete this.sortable;
            if (this.options.allowadd === true) {
                this.watchAddToggle();
                this.watchAdd();
            }
            this.makeSortable();
            this.addMobileSupport();
            this.parent(c);
        }
    });

    return window.FbPicklist;
});