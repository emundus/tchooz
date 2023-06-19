/*! Fabrik */

require(["fab/fabrik","jquery"],function(n,i){n.buildChosen||(n.buildChosen=function(n,s){if(void 0!==i(n).chosen)return i(n).each(function(n,e){var o,a=i(e).data("chosen-options");o=a?i.extend({},s,a):s,i(e).chosen(o),i(e).addClass("chosen-done")}),!0},n.buildAjaxChosen=function(n,e,o){if(void 0!==i(n).ajaxChosen)return i(n).addClass("chosen-done"),i(n).ajaxChosen(e,o)})});