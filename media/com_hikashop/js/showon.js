/**
 * @package    HikaShop for Joomla!
 * @version    5.1.5
 * @author     hikashop.com
 * @copyright  (C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
var hikaShowon = function() {
	this.init();
};
hikaShowon.toggle = function(target , field){
    var showonRule = field.getAttribute('hk-showon');
    var rule = showonRule.split('=');
    if(target.tagName == 'SELECT' || (target.tagName == 'INPUT' && target.type == 'text')) {
        if(target.value == rule[1]) {
            field.style.display = '';
        }else {
            field.style.display = 'none';
        }
    }else if(target.checked && target.value == rule[1]) {
        field.style.display = '';
    } else {
        field.style.display = 'none';
    }
}
hikaShowon.init = function(){
    var fields = document.querySelectorAll('[hk-showon]');
    fields.forEach(function(field) {
        var showonRule = field.getAttribute('hk-showon');
        var rule = showonRule.split('=');
        var targets = document.querySelectorAll('[id^="'+rule[0]+'"]');
        targets.forEach(function(target) {
            var event =  'change';
            if(target.id != rule[0])
                event = 'click';
            // add listener on the target
            target.addEventListener(event, function(e) {
                hikaShowon.toggle(target,field);
            } );
            // init of the state of the elements
            hikaShowon.toggle(target,field);
        });
    });
}
/* showon initialization */
window.hikashop.ready(function(){
	var showon = new hikaShowon();
    showon.init();
});
